<?php

namespace App\Services;

use RuntimeException;

/** Shared question identifiers and printed maxima for QP, MS and student answers. */
class QuestionStructure
{
    public static function canonical(string $number, ?string $part = null): string
    {
        $value = strtolower(trim($number.' '.($part ?? '')));
        preg_match('/(?:question|q)?\s*(\d+)/i', $value, $match);
        if (!$match) throw new RuntimeException('A question identifier must begin with its question number.');
        $digits = (string) (int) $match[1];
        $tail = substr($value, strpos($value, $match[1]) + strlen($match[1]));
        preg_match_all('/[a-z]+|\d+/', $tail, $parts);
        return $digits.implode('', array_map(fn ($p) => '('.$p.')', $parts[0]));
    }

    public function parse(string $qp, float $total, string $ms = ''): array
    {
        $questions = $this->segments($qp);
        if (!$questions) throw new RuntimeException('Question numbers could not be identified in the question paper. Upload a paper with readable question numbers and printed marks.');

        // A parent instruction followed by scored subparts is context, not another question.
        foreach ($questions as $label => $question) {
            $children = array_filter(array_keys($questions), fn ($key) => str_starts_with($key, $label.'('));
            if ($children) {
                foreach ($children as $child) $questions[$child]['text'] = trim($question['text']."\n".$questions[$child]['text']);
                unset($questions[$label]);
            }
        }
        $schemes = trim($ms) !== '' ? (new MarkSchemeStructure())->extract($ms, array_values($questions)) : [];
        foreach ($questions as $label => &$question) {
            $schemeMarks = $schemes[$label]['marks'] ?? null;
            if ($question['marks'] !== null && $schemeMarks !== null && abs($question['marks'] - $schemeMarks) > 0.001) {
                throw new RuntimeException('Printed marks conflict between the question paper and mark scheme for question '.$label.'. Review the uploaded documents.');
            }
            $question['marks'] ??= $schemeMarks;
        }
        unset($question);
        $missing = array_keys(array_filter($questions, fn ($question) => $question['marks'] === null));
        if (count($questions) === 1 && count($missing) === 1) $questions[$missing[0]]['marks'] = $total;
        elseif ($missing) throw new RuntimeException('Maximum marks could not be read for question '.implode(', ', $missing).'. Supply printed per-question marks in the question paper or mark scheme; marks cannot be divided equally.');

        $sum = array_sum(array_column($questions, 'marks'));
        if (abs($sum - $total) > 0.01) throw new RuntimeException('Printed question marks total '.$sum.', but this paper is set to '.$total.'. Update Total marks to '.$sum.' in Paper options, or upload the intended question paper before checking.');
        foreach ($questions as $question) if ($question['marks'] <= 0) throw new RuntimeException('Every scored question must have a positive maximum mark.');
        return array_values($questions);
    }

    public function sections(string $text, array $labels): array
    {
        $result = array_fill_keys($labels, '');
        // Answer content and MS rubric bands are not maximum-mark declarations.
        $segments = $this->segments($text, false);
        foreach ($segments as $label => $section) {
            if (array_key_exists($label, $result)) $result[$label] = $section['text'];
        }
        if (count($labels) === 1 && !$segments) $result[$labels[0]] = trim($text);
        return $result;
    }

    public function schemeSections(string $text, array $questions): array
    {
        $result = array_fill_keys(array_column($questions, 'label'), '');
        foreach ((new MarkSchemeStructure())->extract($text, $questions) as $label => $section) {
            if (array_key_exists($label, $result)) $result[$label] = $section['text'];
        }
        return $result;
    }

    private function segments(string $text, bool $readMarks = true): array
    {
        $lines = $readMarks ? $this->contentLines($text)
            : preg_split('/\R/u', str_replace("\xc2\xa0", ' ', trim($text)));
        $sections = [];
        $current = null;
        $number = null;
        $parts = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $heading = $this->heading($line, $number, $parts);
            // Ranked choices inside Q10 can start "1 ..."; they do not restart Q1.
            if ($readMarks && $heading && $number !== null && (int) $heading[0] < (int) $number) $heading = null;
            if ($heading) {
                [$number, $parts, $content] = $heading;
                $current = self::canonical($number, implode('', array_map(fn ($p) => '('.$p.')', $parts)));
                if (!isset($sections[$current])) $sections[$current] = [
                    'label' => $current, 'question_number' => $number,
                    'question_part' => $parts ? $parts[0].implode('', array_map(fn ($p) => '('.$p.')', array_slice($parts, 1))) : null,
                    'parent_question_number' => $number, 'text' => '', 'marks' => null,
                ];
                $line = $content;
            }
            if ($current === null) continue;
            if ($readMarks && preg_match('/\btotal\b/i', $line)) {
                // An exam-wide total is not a per-question maximum.
                if (preg_match('/^(?:\[|\()?\s*(?:total|end of)/i', $line)) continue;
            } elseif ($readMarks && ($marks = $this->marks($line)) !== null) {
                if ($sections[$current]['marks'] !== null && abs($sections[$current]['marks'] - $marks) > 0.001) {
                    throw new RuntimeException('Conflicting maximum marks were found for question '.$current.'. Review the document layout.');
                }
                $sections[$current]['marks'] = $marks;
            }
            $sections[$current]['text'] = trim($sections[$current]['text']."\n".$line);
        }
        return $sections;
    }

    private function contentLines(string $text): array
    {
        $lines = preg_split('/\R/u', str_replace("\xc2\xa0", ' ', trim($text)));
        $nonempty = array_values(array_filter(array_map('trim', $lines), fn($line) => $line !== ''));
        $content = [];
        $copyrightNotice = false;
        foreach ($nonempty as $index => $line) {
            $next = $nonempty[$index + 1] ?? '';
            // PDF text extraction puts the page number beside the publisher footer.
            // A genuine standalone question number followed by its prompt is kept.
            if (preg_match('/^\d{1,3}$/', $line) && ($this->pageFurniture($next)
                || preg_match('/^Permission to reproduce items\b/i', $next))) continue;
            if ($this->pageFurniture($line)) continue;
            if (preg_match('/^Permission to reproduce items\b/i', $line)) $copyrightNotice = true;
            if ($copyrightNotice && !preg_match('/^\[\s*\d+(?:\.\d+)?\s*\]$/', $line)) continue;
            if (preg_match('/^\d+\s+(?:hours?|minutes?|mins?)\b/i', $line)) continue;
            $content[] = $line;
        }
        return $content;
    }

    private function pageFurniture(string $line): bool
    {
        return (bool) preg_match('/^(?:©|\x{00a9}|copyright\b|Page\s+\d+\s+(?:of|\/)\s*\d+|\[?Turn over\b|PUBLISHED$|\d{3,}\/\d+\s+(?:Checkpoint|Cambridge)|\d{3,}\/\d+\/[A-Z]\/)/iu', $line);
    }

    private function heading(string $line, ?string $number, array $parts): ?array
    {
        if (preg_match('/^(?:(?:question|q)\h*)?\(?(\d+)\)?((?:\h*\([a-zivx]+\))+|[a-z](?=[.):\h])|\h+(?-i:[a-z])(?=[.):\h]))?[.):]?\h+(.*)$/iu', $line, $match)) {
            $newParts = [];
            if (!empty($match[2])) { preg_match_all('/[a-z]+/i', $match[2], $found); $newParts = array_map('strtolower', $found[0]); }
            return [(string) (int) $match[1], $newParts, $match[3]];
        }
        // A question-number line may introduce subparts on the following line.
        if (preg_match('/^(?:question\h+|q\h*)?(\d+)[.):]?$/iu', $line, $match)) return [(string) (int) $match[1], [], ''];
        if ($number !== null && preg_match('/^\(([a-z]+)\)((?:\([a-z]+\))*)[.):]?\h*(.+)$/iu', $line, $match)) {
            $part = strtolower($match[1]);
            $nested = preg_match('/^(?:i|ii|iii|iv|v|vi|vii|viii|ix|x)$/', $part) && $parts && !preg_match('/^(?:i|ii|iii|iv|v|vi|vii|viii|ix|x)$/', $parts[0]);
            $newParts = $nested ? [$parts[0], $part] : [$part];
            if ($match[2] !== '') { preg_match_all('/[a-z]+/i', $match[2], $found); $newParts = array_merge($newParts, array_map('strtolower', $found[0])); }
            return [$number, $newParts, $match[3]];
        }
        return null;
    }

    private function marks(string $line): ?float
    {
        if (preg_match('/\[\s*(\d+(?:\.\d+)?)\s*(?:marks?|points?)?\s*\]\s*$/i', $line, $match)
            || preg_match('/\(\s*(\d+(?:\.\d+)?)\s*(?:marks?|points?)\s*\)\s*$/i', $line, $match)
            || preg_match('/\b(\d+(?:\.\d+)?)\s*(?:marks?|points?)\s*$/i', $line, $match)
            || preg_match('/\t(\d+(?:\.\d+)?)\s*$/', $line, $match)) return (float) $match[1];
        return null;
    }
}
