<?php

namespace App\Services\Ocr;

use App\Contracts\DocumentReader;
use App\Models\Assessment;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Symfony\Component\Process\Process;

class LocalDocumentReader implements DocumentReader
{
    public function __construct(private WordDocumentExtractor $word) {}

    public function read(Assessment $assessment): array
    {
        $assessment->loadMissing('attachments');
        $inserts = [];
        foreach ($assessment->attachments->where('type', 'insert') as $attachment) {
            $inserts[] = $this->extract($attachment->path, 'Insert / Source Booklet');
        }
        return [
            'qp_text' => $this->extract($assessment->question_paper_path, 'Question Paper'),
            'ms_text' => $assessment->mark_scheme_path
                ? $this->extract($assessment->mark_scheme_path, 'Mark Scheme') : null,
            'wa_text' => $this->extract($assessment->written_answer_path, 'Written Answer'),
            'insert_text' => trim(implode("\n\n", $inserts)),
            'source' => 'local-document-reader',
        ];
    }

    private function extract(?string $path, string $label): string
    {
        if (!$path || !Storage::exists($path)) {
            throw new RuntimeException($label.' file is missing. Please upload it again.');
        }
        try {
            $file = Storage::path($path);
            // Content detection also recovers previously stored DOCX files named .zip/.bin.
            $type = $this->word->detect($file);
            $text = match ($type) {
                'pdf' => $this->pdf($file),
                'docx' => $this->word->docx($file),
                'doc', 'rtf' => $this->word->doc($file),
                default => throw new RuntimeException('Unsupported or damaged file content. Upload a genuine PDF, DOC or DOCX file.'),
            };
            $text = trim(preg_replace('/^\x{FEFF}/u', '', $text) ?? $text);
            if ($text === '') {
                throw new RuntimeException('No readable text was found. For an image-only Word file, export to PDF and use OCR, or upload a typed DOCX. Password-protected documents must be unlocked first.');
            }
            return $text;
        } catch (\Throwable $e) {
            throw new RuntimeException($label.': '.$e->getMessage(), 0, $e);
        }
    }

    private function pdf(string $file): string
    {
        try {
            $text = trim((new Parser())->parseFile($file)->getText());
            if ($text !== '') return $text;
        } catch (\Throwable $e) {
            // Try Poppler for PDFs unsupported by the PHP parser.
        }
        try {
            $process = new Process([config('exam-marker.ocr_tools.pdftotext', 'pdftotext'), '-layout', $file, '-']);
            $process->setTimeout(60);
            $process->run();
            if ($process->isSuccessful() && trim($process->getOutput()) !== '') return trim($process->getOutput());
        } catch (\Throwable $e) {
            // Image-only PDFs need OCR below.
        }
        return $this->ocr($file);
    }

    private function ocr(string $file): string
    {
        $base = tempnam(sys_get_temp_dir(), 'em-ocr-');
        if ($base === false) throw new RuntimeException('The server temporary directory is not writable.');
        @unlink($base);
        if (!mkdir($base, 0700)) throw new RuntimeException('Unable to prepare PDF OCR.');
        try {
            $process = new Process([config('exam-marker.ocr_tools.pdftoppm', 'pdftoppm'), '-r', '150', '-png', $file, $base.'/page']);
            $process->setTimeout(90);
            $process->mustRun();
            $images = glob($base.'/page-*.png') ?: [];
            natsort($images);
            $text = [];
            foreach ($images as $image) {
                $ocr = new Process([config('exam-marker.ocr_tools.tesseract', 'tesseract'), $image, 'stdout']);
                $ocr->setTimeout(60);
                $ocr->mustRun();
                $text[] = $ocr->getOutput();
            }
            return trim(implode("\n", $text));
        } catch (\Throwable $e) {
            throw new RuntimeException('PDF text could not be extracted. Scanned PDFs require Poppler (pdftoppm) and Tesseract on the server, with process execution enabled.', 0, $e);
        } finally {
            foreach (glob($base.'/*') ?: [] as $image) @unlink($image);
            @rmdir($base);
        }
    }
}
