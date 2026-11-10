<?php

namespace Tests\Feature;

use App\Services\Ocr\LegacyDocTextExtractor;
use RuntimeException;
use Tests\TestCase;

class LegacyDocTextExtractorTest extends TestCase
{
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) @unlink($file);
        parent::tearDown();
    }

    public function test_reads_compressed_and_unicode_pieces_in_document_order(): void
    {
        $bytes = $this->document([
            ['1 Caf'.chr(0xe9)." answer\r", true],
            ["2 বাংলা উত্তর 😀\r", false],
        ]);
        $this->assertSame("1 Café answer\n2 বাংলা উত্তর 😀", $this->extract($bytes));
    }

    public function test_reads_a_piece_table_stored_in_the_compound_mini_stream(): void
    {
        $this->assertSame("1 First answer\n2 Second answer", $this->extract($this->document([
            ["1 First answer\r2 Second answer\r", true],
        ], mini: true)));
    }

    public function test_reads_only_the_main_document_and_keeps_field_results(): void
    {
        $body = "1 answer\rPage \x13 PAGE \x14 4\x15\r";
        $bytes = $this->document([[$body, true], ["Internal footnote text\r", true]], mainLength: strlen($body));
        $this->assertSame("1 answer\nPage  4", $this->extract($bytes));
    }

    public function test_rejects_password_protection_with_actionable_message(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('password-protected');
        $this->extract($this->document([["1 Answer\r", true]], flags: 0x0300));
    }

    public function test_rejects_cyclic_sector_chains_instead_of_hanging(): void
    {
        $bytes = $this->document([["1 Answer\r", true]]);
        $bytes = substr_replace($bytes, pack('V', 2), 512 + 2 * 4, 4);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('damaged');
        $this->extract($bytes);
    }

    public function test_rejects_binary_garbage_and_empty_documents(): void
    {
        foreach ([str_repeat('garbage', 100), $this->document([["\r", true]])] as $bytes) {
            try {
                $this->extract($bytes);
                $this->fail('Invalid or empty DOC must not reach the marker as document text.');
            } catch (RuntimeException $error) {
                $this->assertStringContainsString('DOC', $error->getMessage());
            }
        }
    }

    private function extract(string $bytes): string
    {
        // Prevent an installed external converter from hiding malformed fixture failures.
        config(['exam-marker.ocr_tools.antiword' => 'exam-marker-unavailable-antiword']);
        $file = tempnam(sys_get_temp_dir(), 'exam-marker-doc-');
        $this->files[] = $file;
        file_put_contents($file, $bytes);
        return (new LegacyDocTextExtractor())->extract($file);
    }

    /** Build a minimal Word 97 CFB file, with actual FIB, CLX and piece table records. */
    private function document(array $pieces, bool $mini = false, int $flags = 0x0200, ?int $mainLength = null): string
    {
        $word = str_repeat("\0", 4096);
        $word = substr_replace($word, pack('v2', 0xa5ec, 0x00c1), 0, 4);
        $word = substr_replace($word, pack('v', $flags), 10, 2);
        $word = substr_replace($word, pack('v', 14), 32, 2);
        $word = substr_replace($word, pack('v', 22), 62, 2);
        $word = substr_replace($word, pack('v', 93), 152, 2);
        $positions = [0];
        $pcds = '';
        foreach ($pieces as $i => [$text, $compressed]) {
            $encoded = $compressed ? $text : mb_convert_encoding($text, 'UTF-16LE', 'UTF-8');
            $characters = $compressed ? strlen($encoded) : intdiv(strlen($encoded), 2);
            $positions[] = end($positions) + $characters;
            $offset = 1024 + $i * 1024;
            $word = substr_replace($word, $encoded, $offset, strlen($encoded));
            $fc = $compressed ? (0x40000000 | $offset * 2) : $offset;
            $pcds .= pack('vVv', 0, $fc, 0);
        }
        $word = substr_replace($word, pack('V', $mainLength ?? end($positions)), 76, 4);
        $plc = pack('V*', ...$positions).$pcds;
        $clx = "\x01".pack('v', 3).'abc'."\x02".pack('V', strlen($plc)).$plc;
        $word = substr_replace($word, pack('V2', 0, strlen($clx)), 418, 8);

        $header = str_repeat("\0", 512);
        $header = substr_replace($header, hex2bin('d0cf11e0a1b11ae1'), 0, 8);
        foreach ([24 => 0x003e, 26 => 3, 28 => 0xfffe, 30 => 9, 32 => 6] as $offset => $value) {
            $header = substr_replace($header, pack('v', $value), $offset, 2);
        }
        foreach ([44 => 1, 48 => 1, 56 => 4096, 60 => $mini ? 10 : 0xfffffffe, 64 => $mini ? 1 : 0, 68 => 0xfffffffe] as $offset => $value) {
            $header = substr_replace($header, pack('V', $value), $offset, 4);
        }
        $header = substr_replace($header, pack('V', 0).str_repeat(pack('V', 0xffffffff), 108), 76, 436);
        $fat = array_fill(0, 128, 0xffffffff);
        $fat[0] = 0xfffffffd;
        $fat[1] = 0xfffffffe;
        for ($id = 2; $id <= 9; $id++) $fat[$id] = $id === 9 ? 0xfffffffe : $id + 1;
        $directory = $this->entry('Root Entry', 5, $mini ? 11 : 0xfffffffe, $mini ? 64 : 0)
            .$this->entry('WordDocument', 2, 2, 4096)
            .$this->entry('1Table', 2, $mini ? 0 : 10, $mini ? strlen($clx) : 4096)
            .str_repeat("\0", 128);
        if ($mini) {
            $fat[10] = $fat[11] = 0xfffffffe;
            $tail = pack('V', 0xfffffffe).str_repeat(pack('V', 0xffffffff), 127).str_pad($clx, 512, "\0");
        } else {
            for ($id = 10; $id <= 17; $id++) $fat[$id] = $id === 17 ? 0xfffffffe : $id + 1;
            $tail = str_pad($clx, 4096, "\0");
        }
        return $header.pack('V*', ...$fat).$directory.$word.$tail;
    }

    private function entry(string $name, int $type, int $start, int $size): string
    {
        $entry = str_repeat("\0", 128);
        $encoded = mb_convert_encoding($name."\0", 'UTF-16LE', 'UTF-8');
        $entry = substr_replace($entry, $encoded, 0, strlen($encoded));
        $entry = substr_replace($entry, pack('vCC', strlen($encoded), $type, 1), 64, 4);
        $entry = substr_replace($entry, pack('V3', 0xffffffff, 0xffffffff, 0xffffffff), 68, 12);
        if ($type === 5) $entry = substr_replace($entry, pack('V', 1), 76, 4);
        if ($name === 'WordDocument') $entry = substr_replace($entry, pack('V', 2), 72, 4);
        return substr_replace($entry, pack('V3', $start, $size, 0), 116, 12);
    }
}
