<?php

namespace Tests\Feature;

use App\Models\{Assessment, Student, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssessmentAccessWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_upload_selectors_follow_the_account_permissions(): void
    {
        foreach ([
            'free.teacher@example.com' => [false, false],
            'mode1@example.com' => [true, false],
            'mode3@example.com' => [true, true],
            'mode3.teacher@example.com' => [true, false],
            'student@example.com' => [false, false],
        ] as $email => [$students, $teachers]) {
            $response = $this->actingAs(User::where('email', $email)->firstOrFail())
                ->get(route('assessments.create'))->assertOk();
            foreach (['student_id' => $students, 'assigned_teacher_id' => $teachers] as $field => $visible) {
                if ($visible) $response->assertSee('name="'.$field.'"', false);
                else $response->assertDontSee('name="'.$field.'"', false);
            }
        }
    }

    public function test_free_usage_is_lifetime_and_basic_results_do_not_expose_paid_fields(): void
    {
        $user = User::where('email', 'free.teacher@example.com')->firstOrFail();
        $paper = Assessment::create([
            'user_id' => $user->id, 'title' => 'Previous month paper', 'subject' => 'Math',
            'total_marks' => 10, 'status' => 'review_required',
            'question_paper_path' => 'test/question.pdf', 'written_answer_path' => 'test/answer.pdf',
            'percentage' => 70, 'grade_awarded' => 'B', 'created_at' => now()->subMonths(2),
        ]);
        $paper->results()->create([
            'question_number' => '1', 'max_marks' => 10, 'ai_marks' => 7, 'feedback' => 'Basic feedback.',
        ]);

        $this->actingAs($user)->get(route('assessments.create'))->assertRedirect(route('billing.index'));
        $this->get(route('billing.index'))->assertOk()->assertSee('Lifetime paper checks used')
            ->assertViewHas('used', 1);
        $billing = new \DOMDocument();
        @$billing->loadHTML($this->get(route('billing.index'))->getContent());
        $freeCard = (new \DOMXPath($billing))->query('//article[.//span[@class="plan-kicker" and text()="free"]]')->item(0);
        $this->assertNotNull($freeCard);
        $this->assertStringNotContainsString('students', $freeCard->textContent);
        $this->assertStringContainsString('paper checks in total', $freeCard->textContent);
        $this->get(route('results.show', $paper))->assertOk()->assertSee('Obtained Marks')
            ->assertSee('Basic feedback.')->assertDontSee('<small>Percentage</small>', false)
            ->assertDontSee('<small>Grade</small>', false)->assertDontSee('Download Report')
            ->assertDontSee('name="teacher_marks"', false);
        $this->get(route('assessments.index'))->assertOk()->assertDontSee('70.00%');
        $this->post(route('assessments.store'), [])->assertUnprocessable();
    }

    public function test_staff_uploads_remain_assigned_and_visible_in_single_and_bulk_flows(): void
    {
        Storage::fake('local');
        $teacher = User::where('email', 'mode3.teacher@example.com')->firstOrFail();
        $profile = Student::where('user_id', $teacher->owner_user_id)->firstOrFail();
        $this->actingAs($teacher);

        $this->post(route('assessments.store'), [
            'title' => 'Staff single upload', 'subject' => 'Math', 'total_marks' => 10,
            'student_id' => $profile->id,
            'question_paper' => UploadedFile::fake()->createWithContent('question.pdf', file_get_contents(__DIR__.'/../Fixtures/documents/question.pdf')),
            'written_answer' => UploadedFile::fake()->createWithContent('answer.pdf', file_get_contents(__DIR__.'/../Fixtures/documents/question.pdf')),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->post(route('assessments.bulk.store'), [
            'title' => 'Staff bulk upload', 'subject' => 'Math', 'total_marks' => 10,
            'student_ids' => [$profile->id], 'student_names' => [$profile->name],
            'question_paper' => UploadedFile::fake()->createWithContent('question.pdf', file_get_contents(__DIR__.'/../Fixtures/documents/question.pdf')),
            'written_answers' => [UploadedFile::fake()->createWithContent('answer.pdf', file_get_contents(__DIR__.'/../Fixtures/documents/question.pdf'))],
        ])->assertRedirect(route('assessments.index'))->assertSessionHasNoErrors();

        foreach (['Staff single upload', 'Staff bulk upload'] as $title) {
            $paper = Assessment::where('title', $title)->firstOrFail();
            $this->assertSame($teacher->owner_user_id, $paper->user_id);
            $this->assertSame($teacher->id, $paper->assigned_teacher_id);
            $this->get(route('assessments.show', $paper))->assertOk();
        }
        $this->get(route('portal.teacher'))->assertOk()->assertSee('Staff single upload')->assertSee('Staff bulk upload');
    }
}
