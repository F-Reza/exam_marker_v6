<?php

namespace App\Services;

use App\Models\Assessment;
use RuntimeException;

class MarkingPayloadValidator
{
    public function validate(Assessment $assessment, array $payload): array
    {
        $questions = $payload['questions'] ?? null;
        if (!is_array($questions) || !$questions || count($questions) > 1000) {
            throw new RuntimeException('The marking response must contain readable question results.');
        }
        $seen = [];
        $maximum = $obtained = 0;
        foreach ($questions as &$question) {
            if (!is_array($question)) {
                throw new RuntimeException('Invalid question result in the marking response.');
            }
            $number = trim((string) ($question['question_number'] ?? ''));
            $part = trim((string) ($question['question_part'] ?? ''));
            $key = QuestionStructure::canonical($number, $part);
            $max = $question['max_marks'] ?? null;
            $marks = $question['ai_marks'] ?? $question['marks'] ?? null;
            if ($key === '' || isset($seen[$key]) || !is_numeric($max) || !is_numeric($marks)
                || !is_finite((float) $max) || !is_finite((float) $marks)
                || (float) $max <= 0 || (float) $marks < 0 || (float) $marks > (float) $max) {
                throw new RuntimeException('The marking response contains duplicate questions or marks outside the allowed range.');
            }
            $confidence = strtolower((string) ($question['confidence'] ?? 'medium'));
            if (!in_array($confidence, ['low', 'medium', 'high'], true)) {
                throw new RuntimeException('The marking response contains an invalid confidence value.');
            }
            if (isset($question['writing_rubric']) && !is_array($question['writing_rubric'])) {
                throw new RuntimeException('The marking response contains an invalid writing rubric.');
            }
            foreach ($question['writing_rubric'] ?? [] as $criterion) {
                if (!is_array($criterion) || !is_numeric($criterion['awarded'] ?? null)
                    || !is_numeric($criterion['max_marks'] ?? null)
                    || $criterion['awarded'] < 0 || $criterion['awarded'] > $criterion['max_marks']) {
                    throw new RuntimeException('The writing rubric contains invalid criterion marks.');
                }
            }
            $rubric=$question['writing_rubric']??[];
            if($rubric && (abs(array_sum(array_column($rubric,'max_marks'))-(float)$max)>0.011 || abs(array_sum(array_column($rubric,'awarded'))-(float)$marks)>0.011)) {
                throw new RuntimeException('Writing rubric totals do not match the question marks.');
            }
            if(isset($question['criteria'])&&!is_array($question['criteria'])) throw new RuntimeException('Question criteria must be an array.');
            $threshold=(int)(\App\Models\SystemSetting::where('key','ai_confidence')->value('value')??0);
            if(['low'=>30,'medium'=>60,'high'=>90][$confidence]<$threshold) $confidence='low';
            $seen[$key] = true;
            $question['question_number'] = $number;
            $question['question_part'] = $part !== '' ? $part : null;
            $question['max_marks'] = (float) $max;
            $question['ai_marks'] = (float) $marks;
            $question['confidence'] = $confidence;
            $maximum += (float) $max;
            $obtained += (float) $marks;
        }
        unset($question);
        if (abs($maximum - (float) $assessment->total_marks) > 0.011) {
            throw new RuntimeException('Question maximum marks do not match the assessment total. Check the uploaded question paper, mark scheme and total marks.');
        }
        $percentage = round($obtained / $maximum * 100, 2);
        if(isset($payload['percentage']) && (!is_numeric($payload['percentage']) || abs((float)$payload['percentage']-$percentage)>0.11)) {
            throw new RuntimeException('The marking response total does not match its question marks.');
        }
        return array_replace($payload, [
            'questions' => $questions,
            'percentage' => $percentage,
            'grade' => GradingService::grade($percentage, $assessment),
        ]);
    }
}
