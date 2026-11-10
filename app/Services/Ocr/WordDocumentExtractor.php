<?php

namespace App\Services\Ocr;

use DOMDocument;
use DOMNode;
use PharData;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

/** Reads Word text without trusting the stored extension or executing document macros. */
class WordDocumentExtractor
{
    private const MAX_XML_BYTES = 32 * 1024 * 1024;

    public function __construct(private bool $preferNativeZip = true) {}

    public function detect(string $file): ?string
    {
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            throw new RuntimeException('The uploaded document could not be opened. Please upload it again.');
        }
        $prefix = fread($handle, 1024);
        fclose($handle);
        if (strpos($prefix, '%PDF-') !== false) return 'pdf';
        if (str_starts_with($prefix, "PK\x03\x04")) return 'docx';
        if (str_starts_with($prefix, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")) return 'doc';
        if (str_starts_with(ltrim($prefix), '{\\rtf')) return 'rtf';
        return null;
    }

    public function docx(string $file): string
    {
        if ($this->preferNativeZip && class_exists(ZipArchive::class)) return (new DocxTextExtractor())->extract($file);
        return $this->xmlText($this->documentXml($file));
    }

    /** ZipArchive is preferred; PharData supports DOCX on hosts without ext-zip. */
    public function documentXml(string $file): string
    {
        if ($this->preferNativeZip && class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            if ($zip->open($file) !== true) {
                throw new RuntimeException('The DOCX file is damaged or password protected. Save an unprotected copy and upload it again.');
            }
            try {
                $stat = $zip->statName('word/document.xml');
                if (!$stat || $stat['size'] > self::MAX_XML_BYTES) {
                    throw new RuntimeException('The DOCX file has no readable Word document or exceeds the extraction limit.');
                }
                $xml = $zip->getFromName('word/document.xml');
                if (!is_string($xml)) throw new RuntimeException('The DOCX document could not be read.');
                return $xml;
            } finally {
                $zip->close();
            }
        }
        if (!class_exists(PharData::class)) {
            throw new RuntimeException('DOCX reading requires PHP ZipArchive or PharData. Ask the server administrator to enable PHP zip or phar.');
        }
        $temp = tempnam(sys_get_temp_dir(), 'em-word-');
        if ($temp === false) throw new RuntimeException('The server temporary directory is not writable.');
        $archive = $temp.'.zip';
        try {
            if (!copy($file, $archive)) throw new RuntimeException('Unable to prepare the Word document for reading.');
            $zip = new PharData($archive);
            if (!isset($zip['word/document.xml']) || $zip['word/document.xml']->getSize() > self::MAX_XML_BYTES) {
                throw new RuntimeException('The file is not a readable DOCX document or exceeds the extraction limit.');
            }
            return $zip['word/document.xml']->getContent();
        } catch (\Throwable $e) {
            throw new RuntimeException('DOCX could not be opened. Use an unprotected Word DOCX file; the server needs PHP zip or phar support.', 0, $e);
        } finally {
            unset($zip);
            @unlink($archive);
            @unlink($temp);
        }
    }

    private function xmlText(string $xml): string
    {
        if (!class_exists(DOMDocument::class)) {
            throw new RuntimeException('Word reading requires the PHP XML extension. Ask the server administrator to enable php-xml.');
        }
        if (stripos($xml, '<!DOCTYPE') !== false) throw new RuntimeException('Unsupported XML content in the Word document.');
        $previous = libxml_use_internal_errors(true);
        try {
            $doc = new DOMDocument();
            if (!$doc->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) {
                throw new RuntimeException('The Word document XML is damaged. Save a fresh DOCX copy and upload it again.');
            }
            $text = '';
            $visit = function (DOMNode $node) use (&$visit, &$text): void {
                // Deleted text and field instructions are not part of the visible answer.
                if (in_array($node->localName, ['del', 'instrText'], true)) return;
                if ($node->localName === 't') { $text .= $node->textContent; return; }
                if ($node->localName === 'tab') { $text .= "\t"; return; }
                if (in_array($node->localName, ['br', 'cr'], true)) { $text .= "\n"; return; }
                foreach ($node->childNodes as $child) $visit($child);
                if (in_array($node->localName, ['p', 'tr'], true)) $text .= "\n";
                if ($node->localName === 'tc') $text .= "\t";
            };
            $visit($doc->documentElement);
            return trim(str_replace("\xC2\xA0", ' ', $text));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    public function doc(string $file): string
    {
        if ($this->detect($file) === 'doc') {
            try { return (new LegacyDocTextExtractor())->extract($file); }
            catch (RuntimeException $e) { if (str_contains(strtolower($e->getMessage()), 'password')) throw $e; }
        }
        try {
            $process = new Process([config('exam-marker.word_tools.antiword', 'antiword'), '-m', 'UTF-8.txt', $file]);
            $process->setTimeout(60);
            $process->run();
            if ($process->isSuccessful() && trim($process->getOutput()) !== '') return trim($process->getOutput());
        } catch (\Throwable $e) {
            // Try an isolated LibreOffice conversion when antiword is unavailable or cannot read this file.
        }
        return $this->officeText($file);
    }

    private function officeText(string $file): string
    {
        $base = tempnam(sys_get_temp_dir(), 'em-office-');
        if ($base === false) throw new RuntimeException('The server temporary directory is not writable.');
        @unlink($base);
        if (!mkdir($base, 0700)) throw new RuntimeException('Unable to create a temporary document directory.');
        try {
            $extension = $this->detect($file) === 'rtf' ? 'rtf' : 'doc';
            if (!copy($file, $base.'/input.'.$extension)) throw new RuntimeException('Unable to prepare the DOC file.');
            $profile = str_replace('\\', '/', $base.'/profile');
            $profileUri = 'file://'.(str_starts_with($profile, '/') ? '' : '/').str_replace(['%2F', '%3A'], ['/', ':'], rawurlencode($profile));
            $process = new Process([
                config('exam-marker.word_tools.libreoffice', 'soffice'),
                '-env:UserInstallation='.$profileUri, '--headless', '--convert-to',
                'txt:Text (encoded):UTF8', '--outdir', $base, $base.'/input.'.$extension,
            ]);
            $process->setTimeout(90);
            $process->run();
            $output = $base.'/input.txt';
            if ($process->isSuccessful() && is_file($output)) {
                $text = trim((string) file_get_contents($output));
                if ($text !== '') return $text;
            }
        } catch (\Throwable $e) {
            // Return a useful server dependency error instead of silently treating the answer as blank.
        } finally {
            $this->removeDirectory($base);
        }
        throw new RuntimeException('DOC text could not be read. The server needs antiword or LibreOffice (soffice), with process execution enabled. Alternatively, save this file as an unprotected DOCX and upload it again.');
    }

    private function removeDirectory(string $directory): void
    {
        foreach (new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS) as $item) {
            if ($item->isDir() && !$item->isLink()) $this->removeDirectory($item->getPathname());
            else @unlink($item->getPathname());
        }
        @rmdir($directory);
    }
}
