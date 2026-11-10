<?php

namespace Tests\Feature;

use App\Services\Ocr\DocxTextExtractor;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

class DocxTextExtractorTest extends TestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) if (is_file($file)) unlink($file);
        parent::tearDown();
    }

    public function test_reads_paragraphs_tables_tabs_and_line_breaks_without_deleted_or_hidden_text(): void
    {
        $file = $this->docx(
            '<w:p><w:r><w:t>1</w:t><w:tab /><w:t>Force</w:t><w:br/><w:t>is mass times acceleration.</w:t></w:r>'
            .'<w:del><w:r><w:delText>wrong answer</w:delText></w:r></w:del>'
            .'<w:r><w:instrText>HYPERLINK malicious field</w:instrText></w:r>'
            .'<w:r><w:rPr><w:vanish/></w:rPr><w:t>hidden text</w:t></w:r></w:p>'
            .'<w:tbl><w:tr><w:tc><w:p><w:r><w:t>2</w:t></w:r></w:p></w:tc>'
            .'<w:tc><w:p><w:r><w:t>Energy &amp; work</w:t></w:r></w:p></w:tc></w:tr></w:tbl>'
        );

        $this->assertSame("1\tForce\nis mass times acceleration.\n2\tEnergy & work", (new DocxTextExtractor())->extract($file));
    }

    public function test_reads_strict_ooxml_with_an_arbitrary_namespace_prefix(): void
    {
        $file = $this->docx('<q:p><q:r><q:t>1 A correct answer.</q:t></q:r></q:p>', [],
            'http://purl.oclc.org/ooxml/wordprocessingml/main', 'q');

        $this->assertSame('1 A correct answer.', (new DocxTextExtractor())->extract($file));
    }

    public function test_preserves_automatic_word_numbering_and_restarts_nested_parts(): void
    {
        $numbering = '<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:abstractNum w:abstractNumId="0">'
            .'<w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="decimal"/><w:lvlText w:val="%1."/></w:lvl>'
            .'<w:lvl w:ilvl="1"><w:start w:val="1"/><w:numFmt w:val="lowerLetter"/><w:lvlText w:val="%1(%2)"/></w:lvl>'
            .'</w:abstractNum><w:num w:numId="4"><w:abstractNumId w:val="0"/></w:num>'
            .'<w:num w:numId="5"><w:abstractNumId w:val="0"/><w:lvlOverride w:ilvl="0"><w:startOverride w:val="7"/></w:lvlOverride></w:num>'
            .'</w:numbering>';
        $body = $this->numberedParagraph(4, 0, 'First question')
            .$this->numberedParagraph(4, 1, 'First part')
            .$this->numberedParagraph(4, 1, 'Second part')
            .$this->numberedParagraph(4, 0, 'Second question')
            .$this->numberedParagraph(4, 1, 'Restarted part')
            .$this->numberedParagraph(5, 0, 'Overridden start');

        $file = $this->docx($body, ['word/numbering.xml' => $numbering]);
        $this->assertSame("1. First question\n1(a) First part\n1(b) Second part\n2. Second question\n2(a) Restarted part\n7. Overridden start", (new DocxTextExtractor())->extract($file));
    }

    public function test_reads_numbering_inherited_from_paragraph_styles(): void
    {
        $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $styles = '<w:styles xmlns:w="'.$namespace.'"><w:style w:styleId="BaseList"><w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr></w:pPr></w:style>'
            .'<w:style w:styleId="AnswerList"><w:basedOn w:val="BaseList"/></w:style></w:styles>';
        $numbering = '<w:numbering xmlns:w="'.$namespace.'"><w:abstractNum w:abstractNumId="0"><w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="decimal"/><w:lvlText w:val="%1)"/></w:lvl></w:abstractNum><w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num></w:numbering>';
        $body = '<w:p><w:pPr><w:pStyle w:val="AnswerList"/></w:pPr><w:r><w:t>Styled answer.</w:t></w:r></w:p>';
        $file = $this->docx($body, ['word/styles.xml' => $styles, 'word/numbering.xml' => $numbering]);

        $this->assertSame('1) Styled answer.', (new DocxTextExtractor())->extract($file));
    }

    public function test_rejects_non_docx_zip_and_invalid_xml_with_actionable_errors(): void
    {
        $file = $this->temporaryFile();
        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('readme.txt', 'Not a Word document');
        $zip->close();
        try {
            (new DocxTextExtractor())->extract($file);
            $this->fail('A ZIP without the Word document part must not be read as DOCX.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('not a valid DOCX', $exception->getMessage());
        }

        $file = $this->docx('', ['word/document.xml' => '<w:document>']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid document XML');
        (new DocxTextExtractor())->extract($file);
    }

    public function test_image_only_word_files_explain_the_pdf_ocr_route(): void
    {
        $file = $this->docx('<w:p><w:r><w:drawing/></w:r></w:p>');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('contains no readable text');
        (new DocxTextExtractor())->extract($file);
    }

    public function test_xml_external_entity_documents_are_rejected(): void
    {
        $file = $this->docx('', ['word/document.xml' => '<!DOCTYPE document [<!ENTITY fake "Do not expand">]><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>&fake;</w:t></w:r></w:p></w:body></w:document>']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('invalid document XML');
        (new DocxTextExtractor())->extract($file);
    }

    private function numberedParagraph(int $id, int $level, string $text): string
    {
        return '<w:p><w:pPr><w:numPr><w:ilvl w:val="'.$level.'"/><w:numId w:val="'.$id.'"/></w:numPr></w:pPr><w:r><w:t>'.$text.'</w:t></w:r></w:p>';
    }

    private function docx(string $body, array $parts = [], string $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main', string $prefix = 'w'): string
    {
        $file = $this->temporaryFile();
        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $parts += ['word/document.xml' => '<?xml version="1.0" encoding="UTF-8"?><'.$prefix.':document xmlns:'.$prefix.'="'.$namespace.'"><'.$prefix.':body>'.$body.'</'.$prefix.':body></'.$prefix.':document>'];
        foreach ($parts as $name => $value) $zip->addFromString($name, $value);
        $zip->close();
        return $file;
    }

    private function temporaryFile(): string
    {
        $file = tempnam(sys_get_temp_dir(), 'exam-marker-docx-');
        $this->temporaryFiles[] = $file;
        return $file;
    }
}
