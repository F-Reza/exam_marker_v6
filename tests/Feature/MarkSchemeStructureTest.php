<?php

namespace Tests\Feature;

use App\Services\MarkSchemeStructure;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MarkSchemeStructureTest extends TestCase
{
    public function test_table_mark_cells_and_subparts_match_known_question_order(): void
    {
        $questions = $this->questions(['1(a)' => 1, '1(b)' => 1, '1(c)' => 2, '2' => 1]);
        $ms = "Question\tAnswer\tMarks\n1(a) Award 1 mark for an appropriate word.\n1\n1(b) Award 1 mark for an alternative word.\n1\n1(c) Award credit for two reasons.\n2\nQuestion\tAnswer\tMarks\n2 Award 1 mark for an explanation.\n1";
        $actual = (new MarkSchemeStructure())->extract($ms, $questions);
        $this->assertSame([1.0, 1.0, 2.0, 1.0], array_column($actual, 'marks'));
        $this->assertStringContainsString('two reasons', $actual['1(c)']['text']);
    }

    public function test_ordering_answers_cannot_reopen_earlier_questions(): void
    {
        $questions = $this->questions(['1' => 1, '2' => 1, '3' => 1, '4' => 1, '5' => 1, '6' => 1, '10' => 4, '11' => 25]);
        $ms = "Question\tAnswer\tMarks\n10 Award credit for the correct sequence.\n5 making useful items\n1 objects used in homes\n2 items on display\n3 places of origin\n6 businesses in large towns\n4 design origins\n4\nQuestion\tAnswer\tMarks\n11 Creation of texts\t5\nVocabulary\t3\nStructure\t7\nGrammar\t7\nSpelling\t3\n[Total 25]";
        $actual = (new MarkSchemeStructure())->extract($ms, $questions);
        $this->assertSame(4.0, $actual['10']['marks']);
        $this->assertStringContainsString('5 making useful items', $actual['10']['text']);
        $this->assertStringContainsString('4 design origins', $actual['10']['text']);
        $this->assertSame('', $actual['1']['text']);
        $this->assertSame(25.0, $actual['11']['marks']);
    }

    public function test_writing_total_is_separate_from_criterion_columns_and_rubric_bands(): void
    {
        $ms = "Question\tAnswer\tMarks\nNotes to markers\nUse the criteria grids.\n11 Creation of texts\t5\nVocabulary\t3\nStructure\t7\nGrammar\t7\nSpelling\t3\n[Total 25]\n9999/01 Example – Mark Scheme May 2026\nPUBLISHED\nPage 7 of 10\nWriting criteria\n[1]\t[2]\t[3]\n[4–5]\nSome relevant ideas.\n[3]";
        $actual = (new MarkSchemeStructure())->extract($ms, $this->questions(['11' => 25]));
        $this->assertSame(25.0, $actual['11']['marks']);
        $this->assertStringContainsString('Use the criteria grids.', $actual['11']['text']);
        $this->assertStringContainsString('[1]', $actual['11']['text']);
        $this->assertStringContainsString('Some relevant ideas.', $actual['11']['text']);
    }

    public function test_ambiguous_criterion_columns_do_not_become_a_guessed_total(): void
    {
        $actual = (new MarkSchemeStructure())->extract("Question\tAnswer\tMarks\n11 Ideas\t5\nLanguage\t3\nOrganisation\t7", $this->questions(['11' => 25]));
        $this->assertNull($actual['11']['marks']);
    }

    public function test_simple_labelled_mark_scheme_still_exposes_its_maximum(): void
    {
        $actual = (new MarkSchemeStructure())->extract("1 Appropriate explanation. [5]\n2 A second explanation. [3]", $this->questions(['1' => 5, '2' => 3]));
        $this->assertSame([5.0, 3.0], array_column($actual, 'marks'));
    }

    public function test_unnumbered_single_question_scheme_keeps_its_full_context(): void
    {
        $ms = 'Award credit for coherent explanations and supporting examples.';
        $actual = (new MarkSchemeStructure())->extract($ms, $this->questions(['1' => 5]));
        $this->assertSame($ms, $actual['1']['text']);
        $this->assertNull($actual['1']['marks']);
    }

    public function test_unnumbered_single_question_scheme_uses_only_an_unambiguous_explicit_total(): void
    {
        $structure = new MarkSchemeStructure();
        $known = $this->questions(['1' => 5]);
        $this->assertSame(5.0, $structure->extract("Use the writing criteria.\n[Total 5]", $known)['1']['marks']);
        $this->assertNull($structure->extract("First criterion\n[Total 2]\nSecond criterion\n[Total 3]", $known)['1']['marks']);
        $multiple = $structure->extract("[Total 5]", $this->questions(['1' => 2, '2' => 3]));
        $this->assertSame([null, null], array_column($multiple, 'marks'));
        $this->assertSame(['', ''], array_column($multiple, 'text'));
    }

    public function test_distinct_explicit_whole_question_totals_still_fail(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Conflicting whole-question maximum marks');
        (new MarkSchemeStructure())->extract("Question\tAnswer\tMarks\n11 Ideas\t5\n[Total 25]\n[Total 20]", $this->questions(['11' => 25]));
    }

    public function test_distinct_labelled_maxima_still_fail(): void
    {
        $this->expectException(RuntimeException::class);
        (new MarkSchemeStructure())->extract("1 Explanation. [5]\n1 Alternative explanation. [4]", $this->questions(['1' => 5]));
    }

    private function questions(array $marks): array
    {
        $result = [];
        foreach ($marks as $label => $maximum) $result[] = ['label' => (string) $label, 'text' => 'Example question', 'marks' => $maximum];
        return $result;
    }
}
