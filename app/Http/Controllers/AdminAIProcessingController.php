<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use Illuminate\Http\Request;

class AdminAIProcessingController extends Controller
{
    public function index()
    {
        // Get all statuses with counts dynamically
        $statusCounts = Assessment::select('status', \DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Map database statuses to display names
        $statusMap = [
            'queued' => 'pending',
            'pending' => 'pending',
            'processing' => 'processing',
            'in_progress' => 'processing',
            'completed' => 'completed',
            'finalised' => 'completed',
            'done' => 'completed',
            'review_required' => 'review_required',
            'needs_review' => 'review_required',
            'uploaded' => 'uploaded',
            'ai_checked' => 'ai_checked',
            'failed' => 'failed',
            'error' => 'failed',
        ];

        // Aggregate counts by display status
        $stats = [];
        foreach ($statusCounts as $dbStatus => $count) {
            $displayStatus = $statusMap[$dbStatus] ?? $dbStatus;
            $stats[$displayStatus] = ($stats[$displayStatus] ?? 0) + $count;
        }

        // Ensure all statuses exist with 0 if not present
        $allStatuses = ['pending', 'processing', 'completed', 'review_required', 'uploaded', 'ai_checked', 'failed'];
        foreach ($allStatuses as $status) {
            if (!isset($stats[$status])) {
                $stats[$status] = 0;
            }
        }

        // Sort stats by key for consistent display
        ksort($stats);

        // Get all jobs with pagination
        $jobs = Assessment::latest()
            ->with('student')
            ->paginate(20);

        return view('admin.ai-processing', compact('stats', 'jobs'));
    }
}