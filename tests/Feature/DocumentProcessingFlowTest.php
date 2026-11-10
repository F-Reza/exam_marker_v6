<?php

namespace Tests\Feature;

use App\Contracts\DocumentReader;
use App\Models\Assessment;
use App\Models\User;
use App\Services\AdaptivePaperMarker;
use App\Services\Ocr\LocalDocumentReader;
use App\Services\PaperMarker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class DocumentProcessingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
        Storage::fake('local');
        Http::preventStrayRequests();
        config(['exam-marker.async' => false, 'logging.default' => 'null']);
        $this->app->bind(DocumentReader::class, LocalDocumentReader::class);
        $this->app->bind(PaperMarker::class, AdaptivePaperMarker::class);
    }

    public function test_mode_1_checks_pdf_documents_with_a_numbered_docx_answer(): void
    {
        $this->checkMixedDocuments('mode1@example.com');
    }

    public function test_mode_2_checks_pdf_documents_with_a_numbered_docx_answer(): void
    {
        $this->checkMixedDocuments('mode2@example.com');
    }

    public function test_mode_2_bulk_accepts_generic_mime_pdf_insert_and_docx_answer(): void
    {
        $this->actingAs(User::where('email', 'mode2@example.com')->firstOrFail());
        $documents = $this->documents();
        // Reproduce servers that report a valid OOXML container as generic ZIP.
        $documents['written_answer']->mimeType('application/zip');
        $this->post(route('assessments.bulk.store'), [
            'title' => 'Mixed bulk documents', 'subject' => 'Physics', 'total_marks' => 10,
            'student_names' => ['Document Test Student'],
            'question_paper' => $documents['question_paper'],
            'mark_scheme' => $documents['mark_scheme'],
            'inserts' => $documents['inserts'],
            'written_answers' => [$documents['written_answer']], 'start_now' => true,
        ])->assertRedirect(route('assessments.index'))->assertSessionHasNoErrors();

        $paper = Assessment::where('title', 'Mixed bulk documents')->firstOrFail();
        $this->assertProcessed($paper);
        $this->assertSame('docx', pathinfo($paper->written_answer_path, PATHINFO_EXTENSION));
        $this->assertSame('pdf', pathinfo($paper->attachments()->firstOrFail()->path, PATHINFO_EXTENSION));
    }

    public function test_existing_word_upload_saved_with_zip_extension_is_detected_by_content(): void
    {
        $user = User::where('email', 'mode1@example.com')->firstOrFail();
        Storage::put('documents/question.pdf', $this->pdf(['1 Describe force and motion.']));
        Storage::put('documents/answer.zip', $this->docx(['Force is a push or pull which changes the motion of an object.']));
        $paper = Assessment::create([
            'user_id' => $user->id, 'title' => 'Previously stored Word upload', 'subject' => 'Physics',
            'total_marks' => 5, 'status' => 'uploaded',
            'question_paper_path' => 'documents/question.pdf', 'written_answer_path' => 'documents/answer.zip',
        ]);

        $this->actingAs($user)->post(route('assessments.start', $paper))
            ->assertRedirect(route('assessments.processing', $paper));
        $this->assertSame('review_required', $paper->fresh()->status);
        $this->assertGreaterThan(0, (float) $paper->results()->firstOrFail()->ai_marks);
    }

    public function test_bad_word_processing_shows_failure_and_retry_instead_of_a_server_error(): void
    {
        $user = User::where('email', 'mode1@example.com')->firstOrFail();
        Storage::put('documents/question.pdf', $this->pdf(['1 Describe force.']));
        Storage::put('documents/broken.docx', 'This is not a Word ZIP package.');
        $paper = Assessment::create([
            'user_id' => $user->id, 'title' => 'Unreadable Word upload', 'subject' => 'Physics',
            'total_marks' => 5, 'status' => 'uploaded',
            'question_paper_path' => 'documents/question.pdf', 'written_answer_path' => 'documents/broken.docx',
        ]);

        $this->actingAs($user)->post(route('assessments.start', $paper))
            ->assertRedirect(route('assessments.show', $paper))->assertSessionHas('error');
        $paper->refresh();
        $this->assertSame('failed', $paper->status);
        $this->assertStringContainsString('Written Answer', $paper->error_message);
        $this->assertNotNull($paper->processing_completed_at);
        $this->assertSame(0, $paper->results()->count());
        $this->get(route('assessments.processing', $paper))->assertOk()
            ->assertSee('Paper checking needs attention')->assertSee('Try again')
            ->assertSee($paper->error_message)->assertDontSee('Math.random', false);
        $this->getJson(route('assessments.status', $paper))->assertOk()
            ->assertJsonPath('status', 'failed')->assertJsonPath('error', $paper->error_message);
    }

    public function test_arbitrary_zip_renamed_to_docx_is_rejected_before_creating_a_paper(): void
    {
        $this->actingAs(User::where('email', 'mode1@example.com')->firstOrFail());
        $documents = $this->documents();
        $documents['inserts'] = [$this->upload('not-word.docx', $this->zip(['notes.txt' => 'Not a Word document.']))];
        $this->post(route('assessments.store'), array_merge([
            'title' => 'Rejected package', 'subject' => 'Physics', 'total_marks' => 10,
        ], $documents))->assertSessionHasErrors('inserts.0');
        $this->assertDatabaseMissing('assessments', ['title' => 'Rejected package']);
    }

    private function checkMixedDocuments(string $email): void
    {
        $this->actingAs(User::where('email', $email)->firstOrFail());
        $documents = $this->documents();
        // This is valid PDF content whose preamble causes fileinfo to classify it as binary.
        $this->assertSame('application/octet-stream', $documents['inserts'][0]->getMimeType());
        $this->assertContains($documents['written_answer']->getMimeType(), [
            'application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);

        $this->post(route('assessments.store'), array_merge([
            'title' => 'Mixed PDF and Word paper', 'subject' => 'Physics', 'total_marks' => 10,
        ], $documents))->assertSessionHasNoErrors()->assertRedirect();
        $paper = Assessment::where('title', 'Mixed PDF and Word paper')->firstOrFail();
        $this->assertSame('docx', pathinfo($paper->written_answer_path, PATHINFO_EXTENSION));
        $this->assertSame('pdf', pathinfo($paper->attachments()->firstOrFail()->path, PATHINFO_EXTENSION));
        $texts = app(DocumentReader::class)->read($paper);
        $this->assertStringContainsString('1. Force is a push', $texts['wa_text']);
        $this->assertStringContainsString('2. Energy is transferred', $texts['wa_text']);
        $this->assertStringContainsString('Source booklet', $texts['insert_text']);

        $this->post(route('assessments.start', $paper))->assertRedirect(route('assessments.processing', $paper));
        $this->assertProcessed($paper);
        $this->get(route('results.show', $paper))->assertOk()->assertSee('Obtained Marks');
    }

    private function assertProcessed(Assessment $paper): void
    {
        $paper->refresh();
        $this->assertSame('review_required', $paper->status, $paper->error_message ?? '');
        $this->assertNull($paper->error_message);
        $this->assertNotNull($paper->processing_started_at);
        $this->assertNotNull($paper->processing_completed_at);
        $this->assertSame(2, $paper->results()->count());
        foreach ($paper->results as $result) {
            $this->assertGreaterThan(0, (float) $result->ai_marks);
            $this->assertNotSame('No readable answer detected.', $result->feedback);
        }
        $this->assertTrue($paper->ai_summary['insert_used']);
    }

    private function documents(): array
    {
        return [
            'question_paper' => $this->upload('question.PDF', $this->pdf([
                '1 Describe force and motion. [5]', '2 Explain energy transfer. [5]',
            ])),
            'mark_scheme' => $this->upload('marking.pdf', $this->pdf([
                '1 Force is a push or pull which changes the motion of an object. [5]',
                '2 Energy is transferred when work is done and can change between different forms. [5]',
            ])),
            'written_answer' => $this->upload('answer.DOCX', $this->docx([
                'Force is a push or pull which changes the motion of an object. Forces can also change the speed or direction of travel.',
                'Energy is transferred when work is done and can change between different forms. The total energy is conserved in a closed system.',
            ])),
            'inserts' => [$this->upload('source.PDF', $this->pdf([
                'Source booklet: force and energy observations.',
            ], str_repeat("\0", 32)))],
        ];
    }

    private function upload(string $name, string $bytes): UploadedFile
    {
        $file = UploadedFile::fake()->createWithContent($name, $bytes);
        // Laravel's fake otherwise guesses MIME from the filename, hiding the real bug.
        return $file->mimeType((new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()));
    }

    private function docx(array $answers): string
    {
        $paragraphs = '';
        foreach ($answers as $answer) {
            $paragraphs .= '<w:p><w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr></w:pPr><w:r><w:t>'.htmlspecialchars($answer, ENT_XML1).'</w:t></w:r></w:p>';
        }
        return $this->zip([
            'metadata/padding.bin' => str_repeat('Padding hides the later Word XML entries from MIME sniffing. ', 128),
            '[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
            'word/document.xml' => '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$paragraphs.'</w:body></w:document>',
            'word/_rels/document.xml.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering" Target="numbering.xml"/></Relationships>',
            'word/numbering.xml' => '<?xml version="1.0"?><w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:abstractNum w:abstractNumId="0"><w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="decimal"/><w:lvlText w:val="%1."/></w:lvl></w:abstractNum><w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num></w:numbering>',
        ]);
    }

    private function zip(array $parts): string
    {
        $path = tempnam(sys_get_temp_dir(), 'exam-marker-docx-');
        try {
            $zip = new ZipArchive();
            $zip->open($path, ZipArchive::OVERWRITE);
            foreach ($parts as $name => $content) {
                $zip->addFromString($name, $content);
                if (str_starts_with($name, 'metadata/')) {
                    $zip->setCompressionName($name, ZipArchive::CM_STORE);
                }
            }
            $zip->close();
            return file_get_contents($path);
        } finally {
            unlink($path);
        }
    }

    private function pdf(array $lines, string $preamble = ''): string
    {
        $stream = "BT /F1 12 Tf 50 780 Td 20 TL\n";
        foreach ($lines as $line) {
            $stream .= '('.str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line).") Tj T*\n";
        }
        $stream .= 'ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            "<< /Length ".strlen($stream)." >>\nstream\n".$stream."\nendstream",
        ];
        $pdf = $preamble."%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    }
}
