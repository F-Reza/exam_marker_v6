<?php

namespace Tests\Feature;

use App\Services\QuestionStructure;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class QuestionStructureLayoutTest extends TestCase
{
    public function test_page_numbers_do_not_steal_the_final_writing_maximum(): void
    {
        $questions = (new QuestionStructure())->parse($this->questionPaper(), 50);
        $maxima = array_column($questions, 'marks', 'label');

        $this->assertCount(15, $questions);
        $this->assertSame(4.0, $maxima['8']);
        $this->assertSame(25.0, $maxima['11']);
        $this->assertSame(4.0, $maxima['10']);
        $this->assertSame(50.0, array_sum($maxima));
        $this->assertStringContainsString('1 example rank', collect($questions)->firstWhere('label', '10')['text']);
    }

    public function test_a_genuine_standalone_question_number_is_preserved(): void
    {
        $questions = (new QuestionStructure())->parse("8\nExplain the result. [4]\n9 Explain another result. [6]", 10);
        $this->assertSame(['8', '9'], array_column($questions, 'label'));
        $this->assertSame([4.0, 6.0], array_column($questions, 'marks'));
    }

    public function test_answer_content_is_not_validated_as_question_maxima(): void
    {
        $sections = (new QuestionStructure())->sections("8 Examples [1]\nOther evidence [2]\n11 Essay [25]\nPUBLISHED\nBand [3]\nBand [5]", ['8', '11']);
        $this->assertStringContainsString('Other evidence [2]', $sections['8']);
        $this->assertStringContainsString('Band [5]', $sections['11']);
        $this->assertStringContainsString('PUBLISHED', $sections['11']);
    }

    public function test_real_conflicting_question_maxima_still_fail(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Conflicting maximum marks');
        (new QuestionStructure())->parse("8 Explain this [4]\nQuestion maximum [5]", 4);
    }

    public function test_wrong_assessment_total_reports_the_correct_total_instead_of_a_layout_error(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Update Total marks to 50 in Paper options');
        (new QuestionStructure())->parse($this->questionPaper(), 30);
    }

    public function test_writing_rubric_columns_and_score_bands_do_not_conflict_with_the_total(): void
    {
        $scheme = "Question\tAnswer\tMarks\n11 Creation of text\t5\nVocabulary\t3\nStructure\t7\nGrammar\t7\nSpelling\t3\n[Total 25]\nPage 7 of 10\nCreation of text\n[1]\t[2]\t[3]\n[4-5]\nSome band description.\n[3]\n";
        $questions = (new QuestionStructure())->parse($this->questionPaper(), 50, $scheme);
        $this->assertSame(25.0, array_column($questions, 'marks', 'label')['11']);
        $this->assertSame(50.0, array_sum(array_column($questions, 'marks')));
    }

    public function test_explicit_question_and_scheme_total_mismatch_still_fails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Printed marks conflict');
        (new QuestionStructure())->parse('8 Explain this [4]', 4, "Question\tAnswer\tMarks\n8 A response.\n[Total 5]");
    }

    public function test_an_uppercase_article_does_not_become_a_subquestion(): void
    {
        $questions = (new QuestionStructure())->parse('2 A student explains the result. [3]', 3);
        $this->assertSame('2', $questions[0]['label']);
        $this->assertStringStartsWith('A student', $questions[0]['text']);
    }

    private function questionPaper(): string
    {
        return <<<'TEXT'
The total mark for this paper is 50.
1 hour 10 minutes
2
© Example Exam Board 2026
1 Read the source.
(a) Explain one word. [1]
(b) Find a second word. [1]
(c) Give evidence. [2]
2 Explain the effect. [1]
3
© Example Exam Board 2026
3 Read the next paragraph.
(a) Find a verb. [1]
(b) Explain the purpose. [1]
4 Read the next section.
(a) Explain a phrase. [1]
(b) Explain punctuation. [1]
5 Give the object. [1]
4
© Example Exam Board 2026
6 Link three phrases. [3]
7 Explain the reason. [2]
8 Give two quotations and explanations. [4]
5
© Example Exam Board 2026
9 Explain the ending. [2]
10 Rank these descriptions.
1 example rank
another description
[4]
6
© Example Exam Board 2026
11 Write your description.
7
© Example Exam Board 2026
8
Permission to reproduce items where third-party material appears has been sought.
Copyright acknowledgement text.
© Example Exam Board 2026
[25]
TEXT;
    }
}
