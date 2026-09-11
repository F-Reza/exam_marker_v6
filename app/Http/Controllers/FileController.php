<?php

namespace App\Http\Controllers;

use App\Models\{Assessment, AssessmentAttachment};
use Illuminate\Support\Facades\Storage;

class FileController extends Controller 
{
    /**
     * Show main assessment files (QP, MS, WA)
     */
    public function show(Assessment $assessment, string $type)
    {
        abort_unless(request()->user()->canAccessAssessment($assessment), 403);
        
        $path = match($type) {
            'qp' => $assessment->question_paper_path,
            'ms' => $assessment->mark_scheme_path,
            'wa' => $assessment->written_answer_path
        };
        
        abort_unless($path && Storage::exists($path), 404);
        return Storage::response($path);
    }
    
    /**
     * Show attachment files (inserts, source booklets, etc.)
     */
    public function attachment(Assessment $assessment, AssessmentAttachment $attachment)
    {
        abort_unless(request()->user()->canAccessAssessment($assessment), 403);
        abort_unless($attachment->assessment_id === $assessment->id, 404);
        abort_unless(Storage::exists($attachment->path), 404);
        
        return Storage::response($attachment->path, $attachment->original_name);
    }
}