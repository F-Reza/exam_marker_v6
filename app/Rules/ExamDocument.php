<?php

namespace App\Rules;

use App\Services\Ocr\WordDocumentExtractor;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/** Accept real exam documents, including DOCX files detected as ZIP by finfo. */
class ExamDocument implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile || !$value->isValid()) {
            $fail('The :attribute upload failed. Please select the document again.');
            return;
        }
        $ext = strtolower($value->getClientOriginalExtension());
        if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) {
            $fail('The :attribute must be a PDF, DOC or DOCX document.');
            return;
        }
        try {
            $reader = app(WordDocumentExtractor::class);
            $type = $reader->detect($value->getPathname());
            $valid = match ($ext) {
                'pdf' => $type === 'pdf',
                'docx' => $type === 'docx' && $reader->documentXml($value->getPathname()) !== '',
                'doc' => $type === 'rtf' || ($type === 'doc' && str_contains((string) file_get_contents($value->getPathname()), "W\0o\0r\0d\0D\0o\0c\0u\0m\0e\0n\0t\0")),
            };
            if (!$valid) $fail('The :attribute contents do not match its file type. Save a fresh PDF, DOC or DOCX copy.');
        } catch (\Throwable $e) {
            $fail($e->getMessage());
        }
    }
}
