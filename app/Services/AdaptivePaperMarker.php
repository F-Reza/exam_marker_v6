<?php

namespace App\Services;

use App\Contracts\DocumentReader;
use App\Models\Assessment;
use App\Models\QuestionResult;
use Illuminate\Support\Str;
use RuntimeException;

class AdaptivePaperMarker implements PaperMarker, QuestionScopedMarker
{
    public function __construct(private DocumentReader $reader) {}

    public function mark(Assessment $assessment): array
    {
        $docs = $this->documents($assessment);
        $structure = new QuestionStructure();
        $questions = $structure->parse($docs['qp_text'], (float) $assessment->total_marks, $docs['ms_text']);
        $labels = array_column($questions, 'label');
        $answers = $structure->sections($docs['wa_text'], $labels);
        $schemes = $structure->sections($docs['ms_text'], $labels);
        $rows = [];
        foreach ($questions as $question) {
            $rows[] = $this->row($assessment, $question, $answers[$question['label']], $schemes[$question['label']], $docs['insert_text']);
        }
        $percentage = round(array_sum(array_column($rows, 'ai_marks')) / (float) $assessment->total_marks * 100, 2);
        return [
            'questions' => $rows, 'percentage' => $percentage,
            'grade' => GradingService::grade($percentage, $assessment),
            'summary' => [
                'strengths' => ['Readable responses were matched to the question paper.'],
                'weaknesses' => ['Every suggested mark requires examiner review.'],
                'notice' => $docs['ms_text'] !== ''
                    ? 'Provisional local text comparison using the official mark scheme. This heuristic does not replace examiner judgement.'
                    : 'No official mark scheme uploaded. Suggested marking uses the question paper and supplied source material and is provisional.',
                'provisional' => true, 'document_reader' => $docs['source'] ?? 'unknown',
                'insert_used' => $docs['insert_text'] !== '', 'engine' => 'Adaptive local text comparison',
            ],
        ];
    }

    public function markQuestion(Assessment $assessment, QuestionResult $question): array
    {
        $docs = $this->documents($assessment);
        $structure = new QuestionStructure();
        $label = QuestionStructure::canonical($question->question_number, $question->question_part);
        $questions = $structure->parse($docs['qp_text'], (float) $assessment->total_marks, $docs['ms_text']);
        $target = collect($questions)->firstWhere('label', $label);
        if (!$target || abs($target['marks'] - (float) $question->max_marks) > 0.001) {
            throw new RuntimeException('The selected question or its printed maximum could not be matched. Check the question paper before rechecking.');
        }
        return $this->row($assessment, $target,
            $structure->sections($docs['wa_text'], [$label])[$label],
            $structure->sections($docs['ms_text'], [$label])[$label], $docs['insert_text']);
    }

    private function documents(Assessment $assessment): array
    {
        $docs = $this->reader->read($assessment);
        foreach (['qp_text' => 'Question Paper', 'wa_text' => 'Written Answer'] as $key => $label) {
            if (trim($docs[$key] ?? '') === '') throw new RuntimeException('Unable to extract '.$label.' text.');
        }
        $docs['ms_text'] = trim($docs['ms_text'] ?? '');
        $docs['insert_text'] = trim($docs['insert_text'] ?? '');
        return $docs;
    }

    private function row(Assessment $assessment, array $question, string $answer, string $scheme, string $insert): array
    {
        $words = $this->tokens($answer);
        $reference = $this->tokens(trim($scheme) !== '' ? $scheme.' '.$insert : $question['text'].' '.$insert);
        $coverage = $reference ? count(array_intersect($reference, $words)) / count($reference) : 0;
        $ratio = $words ? min(1, $coverage * 0.7 + min(1, count($words) / 20) * 0.3) : 0;
        $max = (float) $question['marks'];
        $marks = min($max, max(0, round($max * $ratio * 2) / 2));
        return [
            'question_number' => $question['question_number'], 'question_part' => $question['question_part'],
            'parent_question_number' => $question['parent_question_number'], 'topic' => $assessment->subject,
            'type' => 'normal', 'max_marks' => $max, 'ai_marks' => $marks,
            'confidence' => $scheme !== '' && $coverage >= 0.5 ? 'medium' : 'low',
            'feedback' => !$words ? 'No readable answer detected. Teacher review required.'
                : 'Provisional text comparison: '.$marks.' of '.$max.' marks suggested. Confirm the reasoning and marking points before approval.',
            'criteria' => [
                ['label' => 'Readable answer', 'awarded' => (bool) $words],
                ['label' => 'Reference concepts detected', 'awarded' => $coverage >= 0.4],
                ['label' => 'Source material considered', 'awarded' => trim($insert) !== ''],
            ],
            'status' => 'review_required',
        ];
    }

    private function tokens(string $text): array
    {
        preg_match_all('/[\pL\pN]+/u', Str::lower($text), $matches);
        $stop = ['the', 'and', 'for', 'with', 'that', 'this', 'marks', 'answer', 'describe', 'explain', 'state'];
        return array_values(array_unique(array_filter($matches[0], fn ($word) => mb_strlen($word) > 2 && !in_array($word, $stop, true))));
    }
}
