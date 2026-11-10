<?php

namespace Tests\Feature;

use App\Models\{Assessment, User};
use App\Rules\ExamDocument;
use App\Services\Ocr\{LocalDocumentReader, WordDocumentExtractor};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class WordDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
        Storage::fake('local');
        config(['exam-marker.async' => false, 'exam-marker.ocr' => 'local']);
    }

    public function test_container_failure_marks_paper_failed_and_allows_retry(): void
    {
        $user = User::where('email', 'mode1@example.com')->firstOrFail();
        $this->actingAs($user)->post(route('assessments.store'), $this->payload())->assertRedirect();
        $paper = Assessment::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->app->bind(\App\Services\PaperMarker::class, function () {
            throw new \RuntimeException('Simulated missing dependency');
        });
        $this->post(route('assessments.start', $paper))
            ->assertRedirect(route('assessments.show', $paper))->assertSessionHas('error');
        $this->assertSame('failed', $paper->fresh()->status);
        $this->assertNotNull($paper->fresh()->processing_completed_at);
        $this->get(route('assessments.show', $paper))->assertOk()->assertSee('Start Paper Checking');
        $this->app->bind(\App\Services\PaperMarker::class, \App\Services\AdaptivePaperMarker::class);
        $this->post(route('assessments.start', $paper))->assertSessionMissing('error');
        $this->assertSame('review_required', $paper->fresh()->status);
    }

    public function test_worker_failure_callback_clears_a_stuck_processing_status(): void
    {
        $user = User::where('email', 'mode2@example.com')->firstOrFail();
        $this->actingAs($user)->post(route('assessments.store'), $this->payload())->assertRedirect();
        $paper = Assessment::where('user_id', $user->id)->latest('id')->firstOrFail();
        $paper->update(['status' => 'processing']);
        (new \App\Jobs\ProcessAssessment($paper->id))->failed(new \RuntimeException('Worker timeout'));
        $this->assertSame('failed', $paper->fresh()->status);
        $paper->update(['status' => 'review_required']);
        (new \App\Jobs\ProcessAssessment($paper->id))->failed(new \RuntimeException('Late callback'));
        $this->assertSame('review_required', $paper->fresh()->status);
    }

    private function upload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, file_get_contents(__DIR__.'/../Fixtures/documents/'.$name));
    }

    private function payload(): array
    {
        return [
            'title' => 'Mixed document regression', 'subject' => 'Physics', 'total_marks' => 10,
            'question_paper' => $this->upload('question.pdf'),
            'mark_scheme' => $this->upload('scheme.pdf'),
            'inserts' => [$this->upload('insert.pdf')],
            'written_answer' => $this->upload('answer.docx'),
        ];
    }

    public function test_mode_one_and_two_can_check_pdf_papers_with_a_docx_answer(): void
    {
        foreach (['mode1@example.com', 'mode2@example.com'] as $email) {
            $user = User::where('email', $email)->firstOrFail();
            $this->actingAs($user)->post(route('assessments.store'), $this->payload())
                ->assertRedirect()->assertSessionHasNoErrors();
            $paper = Assessment::where('user_id', $user->id)->latest('id')->firstOrFail();
            $this->assertStringEndsWith('.docx', $paper->written_answer_path);
            $this->post(route('assessments.start', $paper))
                ->assertRedirect(route('assessments.processing', $paper))->assertSessionMissing('error');
            $this->assertSame('review_required', $paper->fresh()->status);
            $this->assertTrue($paper->results()->exists());
            $this->assertNotEmpty(app(LocalDocumentReader::class)->read($paper)['insert_text']);
        }
    }

    public function test_docx_can_be_used_for_all_document_roles_and_ms_is_optional(): void
    {
        $user = User::where('email', 'mode1@example.com')->firstOrFail();
        $data = $this->payload();
        $data['question_paper'] = $this->upload('answer.docx');
        $data['inserts'] = [$this->upload('answer.docx')];
        unset($data['mark_scheme']);
        $this->actingAs($user)->post(route('assessments.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $paper = Assessment::where('user_id', $user->id)->firstOrFail();
        $this->post(route('assessments.start', $paper))->assertRedirect()->assertSessionMissing('error');
        $this->assertSame('review_required', $paper->fresh()->status);
        $this->assertNull($paper->mark_scheme_path);
    }

    public function test_bulk_mode_two_accepts_pdf_insert_and_docx_answers(): void
    {
        $data = $this->payload();
        unset($data['written_answer']);
        $data += ['student_names' => ['One', 'Two'], 'written_answers' => [$this->upload('answer.docx'), $this->upload('answer.docx')], 'start_now' => 1];
        $user = User::where('email', 'mode2@example.com')->firstOrFail();
        $this->actingAs($user)->post(route('assessments.bulk.store'), $data)
            ->assertRedirect(route('assessments.index'))->assertSessionHasNoErrors()->assertSessionMissing('error');
        $papers = Assessment::where('user_id', $user->id)->get();
        $this->assertCount(2, $papers);
        foreach ($papers as $paper) $this->assertSame('review_required', $paper->status);
    }

    public function test_generic_mime_pdf_and_docx_are_validated_by_content(): void
    {
        foreach (['insert.pdf', 'answer.docx'] as $name) {
            $file = new class(__DIR__.'/../Fixtures/documents/'.$name, $name, 'application/octet-stream', null, true) extends UploadedFile {
                public function getMimeType(): ?string { return 'application/octet-stream'; }
            };
            $this->assertFalse(Validator::make(['insert' => $file], ['insert' => [\Illuminate\Validation\Rules\File::types(['pdf', 'doc', 'docx'])]])->passes());
            $this->assertTrue(Validator::make(['insert' => $file], ['insert' => ['required', 'file', 'max:51200', new ExamDocument()]])->passes());
        }
    }

    public function test_unrelated_zip_and_disguised_document_are_rejected(): void
    {
        foreach (['fake.docx', 'fake.pdf', 'fake.doc', 'fake.exe'] as $name) {
            $file = UploadedFile::fake()->createWithContent($name, 'This is not a Word or PDF file');
            $this->assertTrue(Validator::make(['paper' => $file], ['paper' => [new ExamDocument()]])->fails());
        }
        $file = UploadedFile::fake()->createWithContent('renamed.pdf', file_get_contents(__DIR__.'/../Fixtures/documents/answer.docx'));
        $this->assertTrue(Validator::make(['paper' => $file], ['paper' => [new ExamDocument()]])->fails());
    }

    public function test_word_text_and_table_cells_survive_without_native_zip(): void
    {
        $text = (new WordDocumentExtractor(false))->docx(__DIR__.'/../Fixtures/documents/answer.docx');
        $this->assertStringContainsString('force is a push or pull', $text);
        $this->assertStringContainsString('Force changes velocity and shape.', $text);
    }

    public function test_old_docx_saved_with_zip_extension_can_still_be_read(): void
    {
        Storage::put('old/answer.zip', file_get_contents(__DIR__.'/../Fixtures/documents/answer.docx'));
        Storage::put('old/question.pdf', file_get_contents(__DIR__.'/../Fixtures/documents/question.pdf'));
        $paper = new Assessment(['question_paper_path' => 'old/question.pdf', 'written_answer_path' => 'old/answer.zip']);
        $paper->setRelation('attachments', collect());
        $this->assertStringContainsString('force is a push', app(LocalDocumentReader::class)->read($paper)['wa_text']);
    }

    public function test_doc_converter_failure_has_a_clear_dependency_error(): void
    {
        config(['exam-marker.word_tools.antiword' => '/missing/antiword', 'exam-marker.word_tools.libreoffice' => '/missing/soffice']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('server needs antiword or LibreOffice');
        app(WordDocumentExtractor::class)->doc(__DIR__.'/../Fixtures/documents/answer.docx');
    }

    public function test_processing_failure_returns_to_paper_details_with_the_document_error(): void
    {
        $user = User::where('email', 'mode1@example.com')->firstOrFail();
        Storage::put('bad/answer.docx', 'broken document');
        Storage::put('good/question.pdf', file_get_contents(__DIR__.'/../Fixtures/documents/question.pdf'));
        $paper = Assessment::create([
            'user_id' => $user->id, 'title' => 'Invalid document', 'subject' => 'Physics', 'total_marks' => 10,
            'status' => 'uploaded', 'question_paper_path' => 'good/question.pdf', 'written_answer_path' => 'bad/answer.docx',
        ]);
        $this->actingAs($user)->post(route('assessments.start', $paper))
            ->assertRedirect(route('assessments.show', $paper))->assertSessionHas('error');
        $this->assertSame('failed', $paper->fresh()->status);
        $this->assertStringContainsString('Written Answer:', $paper->fresh()->error_message);
    }
}
