<?php

namespace Tests\Feature;

use App\Models\{Assessment, UsageRecord, User};
use App\Services\QuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaperTotalCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_failed_paper_total_can_be_corrected_without_reupload_or_another_reservation(): void
    {
        $owner = User::where('email', 'mode1@example.com')->firstOrFail();
        Storage::put('test/question.pdf', 'existing question');
        Storage::put('test/answer.docx', 'existing answer');
        $paper = Assessment::create([
            'user_id' => $owner->id, 'title' => 'Full paper', 'subject' => 'English',
            'total_marks' => 30, 'status' => 'failed', 'error_message' => 'Wrong total.',
            'question_paper_path' => 'test/question.pdf', 'written_answer_path' => 'test/answer.docx',
        ]);
        UsageRecord::create(['user_id' => $owner->id, 'type' => 'assessment_upload',
            'reference_type' => Assessment::class, 'reference_id' => $paper->id]);

        $this->actingAs($owner)->put(route('assessments.documents', $paper), ['total_marks' => 50])
            ->assertRedirect()->assertSessionHasNoErrors();

        $paper->refresh();
        $this->assertSame('50.00', $paper->total_marks);
        $this->assertSame('uploaded', $paper->status);
        $this->assertNull($paper->error_message);
        $this->assertSame('existing question', Storage::get($paper->question_paper_path));
        $this->assertSame('existing answer', Storage::get($paper->written_answer_path));
        $this->assertSame(1, app(QuotaService::class)->paperUsage($owner));
        $this->assertSame(1, UsageRecord::where('type', 'assessment_upload')->count());
    }

    public function test_total_validation_returns_to_the_paper_and_finalised_results_cannot_be_changed(): void
    {
        $owner = User::where('email', 'mode2@example.com')->firstOrFail();
        $paper = Assessment::create([
            'user_id' => $owner->id, 'title' => 'Protected total', 'subject' => 'English',
            'total_marks' => 30, 'status' => 'failed',
            'question_paper_path' => 'test/q.pdf', 'written_answer_path' => 'test/a.docx',
        ]);
        $this->actingAs($owner)->from(route('assessments.show', $paper))
            ->put(route('assessments.documents', $paper), ['total_marks' => 30])
            ->assertRedirect(route('assessments.show', $paper))->assertSessionHasErrors('total_marks');
        $this->putJson(route('assessments.documents', $paper), ['total_marks' => 0])->assertUnprocessable();
        $paper->update(['status' => 'finalised']);
        $this->putJson(route('assessments.documents', $paper), ['total_marks' => 50])->assertUnprocessable();
        $this->assertSame('30.00', $paper->fresh()->total_marks);
        $this->assertSame('finalised', $paper->fresh()->status);
    }
}
