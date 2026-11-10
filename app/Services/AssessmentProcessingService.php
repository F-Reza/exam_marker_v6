<?php

namespace App\Services;

use App\Jobs\ProcessAssessment;
use App\Models\Assessment;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssessmentProcessingService
{
    public function start(Assessment $assessment): bool
    {
        if (!\App\Models\SystemSetting::enabled('ai_enabled', true)) {
            throw ValidationException::withMessages(['assessment' => 'Paper checking is temporarily disabled by the administrator.']);
        }
        $token = (string) Str::uuid();
        $claimed = Assessment::whereKey($assessment->id)->whereIn('status', ['uploaded', 'failed'])->update([
            'status' => 'queued', 'processing_token' => $token, 'error_message' => null,
            'processing_started_at' => null, 'processing_completed_at' => null,
        ]);
        if (!$claimed) {
            throw ValidationException::withMessages(['assessment' => 'This paper is already checking or has results. Open its current status.']);
        }
        app(AuditService::class)->log($assessment, 'assessment.queued');
        try {
            if (config('exam-marker.async', false)) {
                ProcessAssessment::dispatch($assessment->id, $token);
            } else {
                ProcessAssessment::dispatchSync($assessment->id, $token);
            }
        } catch (\Throwable $e) {
            if ($assessment->fresh()->status !== 'failed') {
                Assessment::whereKey($assessment->id)->where('processing_token', $token)->update([
                    'status' => 'failed', 'error_message' => 'Checking could not start. Please retry or contact support.',
                    'processing_completed_at' => now(),
                ]);
                report($e);
            }
            return false;
        }
        return true;
    }
}
