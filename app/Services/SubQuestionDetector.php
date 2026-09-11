<?php
namespace App\Services;

class SubQuestionDetector
{
    public function detect(string $text): array
    {
        preg_match_all('/(?<!\d)(\d+)\s*\(?([a-z])\)?/i', $text, $matches, PREG_SET_ORDER);
        return array_map(fn($m)=>[
            'question_number'=>$m[1],
            'question_part'=>strtolower($m[2])
        ], $matches);
    }
}
