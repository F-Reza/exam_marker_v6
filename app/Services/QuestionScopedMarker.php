<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\QuestionResult;

interface QuestionScopedMarker
{
    /** Return exactly one freshly marked question, without marking other answers. */
    public function markQuestion(Assessment $assessment, QuestionResult $question): array;
}
