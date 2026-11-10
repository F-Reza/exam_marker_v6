<?php

namespace App\Services;

use RuntimeException;

/** Reads question totals without treating rubric criteria or answer rankings as questions. */
class MarkSchemeStructure
{
    public function extract(string $ms, array $questions): array
    {
        $result = [];
        $positions = [];
        foreach (array_values($questions) as $position => $question) {
            $positions[$question['label']] = $position;
            $result[$question['label']] = ['marks' => null, 'text' => ''];
        }
        if (!$result || trim($ms) === '') return $result;

        $lines = preg_split('/\R/u', str_replace("\xc2\xa0", ' ', trim($ms)));
        $table = (bool) preg_match('/\bQuestion\h+Answer\h+Marks\b/iu', $ms);
        $current = null;
        $number = null;
        $parts = [];
        $position = -1;
        $headerOpen = false;
        $prelude = [];
        $candidates = [];

        foreach ($lines as $rawLine) {
            $line = trim($rawLine);
            if ($line === '' || $this->boilerplate($line)) continue;
            if (preg_match('/^Question\h+Answer\h+Marks$/iu', $line)) {
                $table = true;
                $headerOpen = true;
                $prelude = [];
                continue;
            }

            $heading = $this->heading($line, $number, $parts, $headerOpen || $current === null);
            if ($heading !== null) {
                [$nextNumber, $nextParts, $content] = $heading;
                $label = QuestionStructure::canonical($nextNumber, implode('', array_map(fn ($part) => '('.$part.')', $nextParts)));
                $nextPosition = $positions[$label] ?? null;
                // "2 A suitable response" uses A as an article when only Q2 exists.
                if ($nextPosition === null && isset($positions[$nextNumber]) && $nextParts
                    && preg_match('/^(?:(?:question|q)\h*)?(\d+)[.):]?\h+([a-z]\h+.*)$/iu', $line, $plain)) {
                    $nextParts = [];
                    $content = $plain[2];
                    $label = $nextNumber;
                    $nextPosition = $positions[$label];
                }
                $sameParent = $number === $nextNumber && $nextParts !== [];
                $clearRow = $this->labelledMaximum($content) !== null
                    || $this->columnMaximum($content) !== null
                    || (bool) preg_match('/^(?:award|accept|credit|allow|give|one\h+mark|correct\h+answer)\b/iu', $content);
                if ($nextPosition !== null && $nextPosition >= $position
                    && (!$table || $headerOpen || $sameParent || $label === $current || $clearRow)) {
                    $current = $label;
                    $position = $nextPosition;
                    $number = $nextNumber;
                    $parts = $nextParts;
                    $result[$current]['text'] = trim($result[$current]['text']."\n".implode("\n", $prelude));
                    $prelude = [];
                    $headerOpen = false;
                    $candidates[$current] ??= ['totals' => [], 'labelled' => [], 'columns' => []];
                    $line = $content;
                    if (($maximum = $this->labelledMaximum($content)) !== null) {
                        $candidates[$current]['labelled'][] = $maximum;
                    }
                }
            }

            if ($headerOpen) {
                // General marking notes above the next row belong to that question.
                $prelude[] = $line;
                continue;
            }
            if ($current === null) continue;
            if (($maximum = $this->wholeTotal($line)) !== null) {
                $candidates[$current]['totals'][] = $maximum;
            } elseif ($table && ($maximum = $this->columnMaximum($line)) !== null) {
                $candidates[$current]['columns'][] = $maximum;
            }
            $result[$current]['text'] = trim($result[$current]['text']."\n".$line);
        }

        foreach ($candidates as $label => $values) {
            $totals = $this->unique($values['totals']);
            $labelled = $this->unique($values['labelled']);
            if (count($totals) > 1 || (!$totals && count($labelled) > 1)) {
                throw new RuntimeException('Conflicting whole-question maximum marks were found in the mark scheme for question '.$label.'. Review the uploaded documents.');
            }
            if ($totals && $labelled && count($labelled) === 1 && abs($totals[0] - $labelled[0]) > 0.001) {
                throw new RuntimeException('Conflicting whole-question maximum marks were found in the mark scheme for question '.$label.'. Review the uploaded documents.');
            }
            $columns = $this->unique($values['columns']);
            $result[$label]['marks'] = $totals[0] ?? $labelled[0] ?? (count($columns) === 1 ? $columns[0] : null);
        }
        if (!$candidates && count($result) === 1) {
            $label = array_key_first($result);
            $result[$label]['text'] = trim($ms);
            $totals = [];
            foreach ($lines as $line) {
                if (($maximum = $this->wholeTotal(trim($line))) !== null) $totals[] = $maximum;
            }
            $totals = $this->unique($totals);
            $result[$label]['marks'] = count($totals) === 1 ? $totals[0] : null;
        }
        return $result;
    }

    private function heading(string $line, ?string $number, array $parts, bool $standalone): ?array
    {
        if (preg_match('/^(?:(?:question|q)\h*)?\(?(\d+)\)?((?:\h*\([a-zivx]+\))+|[a-z](?=[.):\h])|\h+[a-z](?=[.):\h]))?[.):]?\h+(.*)$/iu', $line, $match)) {
            preg_match_all('/[a-z]+/i', $match[2] ?? '', $found);
            return [(string) (int) $match[1], array_map('strtolower', $found[0]), $match[3]];
        }
        if ($standalone && preg_match('/^(?:question\h+|q\h*)?(\d+)[.):]?$/iu', $line, $match)) {
            return [(string) (int) $match[1], [], ''];
        }
        if ($number !== null && preg_match('/^\(([a-z]+)\)((?:\h*\([a-z]+\))*)[.):]?\h*(.+)$/iu', $line, $match)) {
            $part = strtolower($match[1]);
            $roman = '/^(?:i|ii|iii|iv|v|vi|vii|viii|ix|x)$/';
            $nextParts = preg_match($roman, $part) && $parts && !preg_match($roman, $parts[0]) ? [$parts[0], $part] : [$part];
            preg_match_all('/[a-z]+/i', $match[2], $found);
            return [$number, array_merge($nextParts, array_map('strtolower', $found[0])), $match[3]];
        }
        return null;
    }

    private function wholeTotal(string $line): ?float
    {
        if (preg_match('/^\[?\h*(?:question\h+)?total(?:\h+marks?)?\h*[:=]?\h*(\d+(?:\.\d+)?)\h*(?:marks?)?\h*\]?$/iu', $line, $match)) {
            return (float) $match[1];
        }
        return null;
    }

    private function labelledMaximum(string $line): ?float
    {
        if (preg_match('/\[\h*(\d+(?:\.\d+)?)\h*(?:marks?|points?)?\h*\]\h*$/iu', $line, $match)
            || preg_match('/\(\h*(\d+(?:\.\d+)?)\h*(?:marks?|points?)\h*\)\h*$/iu', $line, $match)
            || preg_match('/\b(\d+(?:\.\d+)?)\h*(?:marks?|points?)\h*$/iu', $line, $match)) {
            return (float) $match[1];
        }
        return null;
    }

    private function columnMaximum(string $line): ?float
    {
        if (preg_match('/^\d+(?:\.\d+)?$/', $line)) return (float) $line;
        if (preg_match('/(?:\t|\h{2,})(\d+(?:\.\d+)?)\h*$/u', $line, $match)) return (float) $match[1];
        return null;
    }

    private function unique(array $values): array
    {
        return array_values(array_unique($values, SORT_REGULAR));
    }

    private function boilerplate(string $line): bool
    {
        return (bool) preg_match('/^(?:Page\h+\d+\h+of\h+\d+|PUBLISHED|\[?Turn\h+over\]?|.*(?:©|\x{00a9}).*|\d+\/\d+.*Mark\h+Scheme.*)$/iu', $line);
    }
}
