<?php

namespace App\Services\Ocr;

use ZipArchive;

class DocumentFormat
{
    public static function detect(string $file): ?string
    {
        $header = file_get_contents($file, false, null, 0, 1024);
        if ($header === false) {
            return null;
        }

        // Some PDF producers put a BOM or a short preamble before the header.
        if (preg_match('/%PDF-\d\.\d/', $header)) {
            return 'pdf';
        }
        if (str_starts_with($header, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")
            || str_starts_with(ltrim($header), '{\\rtf')) {
            return 'doc';
        }
        if (!str_starts_with($header, 'PK') || !class_exists(ZipArchive::class)) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return null;
        }
        try {
            return $zip->locateName('[Content_Types].xml') !== false
                && $zip->locateName('word/document.xml') !== false ? 'docx' : null;
        } finally {
            $zip->close();
        }
    }
}
