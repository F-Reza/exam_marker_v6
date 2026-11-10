<?php

namespace App\Services;

use App\Models\Assessment;

class GradingService
{
    public static function grade(float $percentage, ?Assessment $assessment = null): string
    {
        $board = strtolower(trim((string) $assessment?->exam_board));
        if ($board === 'ib' || str_contains($board, 'baccalaureate')) {
            return match (true) {
                $percentage >= 80 => '7', $percentage >= 70 => '6',
                $percentage >= 60 => '5', $percentage >= 50 => '4',
                $percentage >= 40 => '3', $percentage >= 30 => '2', default => '1',
            };
        }
        // Consistent application scale; official board boundaries may vary by
        // session and must be confirmed by the examiner before publication.
        return match (true) {
            $percentage >= 90 => '9', $percentage >= 80 => '8',
            $percentage >= 70 => '7', $percentage >= 60 => '6',
            $percentage >= 50 => '5', $percentage >= 40 => '4', default => 'U',
        };
    }
}
