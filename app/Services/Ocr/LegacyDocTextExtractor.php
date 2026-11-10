<?php

namespace App\Services\Ocr;

use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/** Reads the main text story in Word 97-2003 files (MS-CFB / MS-DOC). */
class LegacyDocTextExtractor
{
    private const END = 0xfffffffe;
    private const FREE = 0xffffffff;
    private const MAX_BYTES = 64 * 1024 * 1024;

    private string $bytes;
    private int $sectorSize;
    private int $sectorCount;
    private array $fat = [];
    private array $miniFat = [];
    private string $miniStream = '';

    public function extract(string $file): string
    {
        $size = @filesize($file);
        if ($size === false || $size > self::MAX_BYTES) {
            throw new RuntimeException('The DOC file cannot be read or is too large. Save it as DOCX or PDF and upload it again.');
        }
        $bytes = @file_get_contents($file);
        if ($bytes === false) {
            throw new RuntimeException('The DOC file could not be opened. Please upload it again.');
        }
        try {
            $text = $this->readWord($bytes);
        } catch (RuntimeException $error) {
            // Optional support for older Word variants via a shell-free converter.
            $configured = (string) config('exam-marker.ocr_tools.antiword', 'antiword');
            $binary = (new ExecutableFinder())->find($configured);
            if (!$binary && is_file($configured) && is_executable($configured)) {
                $binary = $configured;
            }
            if (!$binary || str_contains($error->getMessage(), 'password')) {
                throw $error;
            }
            try {
                $process = new Process([$binary, '-m', 'UTF-8.txt', $file]);
                $process->setTimeout(45);
                $process->run();
                $text = $process->isSuccessful() ? trim($process->getOutput()) : '';
            } catch (\Throwable) {
                throw $error;
            }
            if ($text === '' || !mb_check_encoding($text, 'UTF-8')) {
                throw $error;
            }
        }

        $text = $this->cleanText($text);
        if ($text === '') {
            throw new RuntimeException('The DOC file has no readable text. Upload a text-based DOCX or PDF, or run OCR on a scanned document first.');
        }

        return $text;
    }

    private function readWord(string $bytes): string
    {
        $this->bytes = $bytes;
        $this->fat = $this->miniFat = [];
        $this->miniStream = '';
        if (strlen($bytes) < 512 || substr($bytes, 0, 8) !== hex2bin('d0cf11e0a1b11ae1')) {
            throw new RuntimeException('This is not a valid Word DOC file. Open it in Word and save it as DOCX or PDF.');
        }
        $major = $this->u16($bytes, 26);
        $shift = $this->u16($bytes, 30);
        if ($this->u16($bytes, 28) !== 0xfffe || !in_array([$major, $shift], [[3, 9], [4, 12]], true)
            || $this->u16($bytes, 32) !== 6 || $this->u32($bytes, 56) !== 4096) {
            $this->invalid();
        }
        $this->sectorSize = 1 << $shift;
        $this->sectorCount = intdiv(strlen($bytes), $this->sectorSize) - 1;
        if ($this->sectorCount < 2 || strlen($bytes) % $this->sectorSize !== 0) $this->invalid();

        $fatCount = $this->u32($bytes, 44);
        if ($fatCount < 1 || $fatCount > $this->sectorCount) $this->invalid();
        $fatIds = array_values(array_filter(array_values(unpack('V*', substr($bytes, 76, 436))), fn ($id) => $id !== self::FREE));
        $difat = $this->u32($bytes, 68);
        $difatCount = $this->u32($bytes, 72);
        $seen = [];
        if ($difatCount > $this->sectorCount) $this->invalid();
        for ($i = 0; $i < $difatCount; $i++) {
            if (isset($seen[$difat])) $this->invalid();
            $seen[$difat] = true;
            $ids = array_values(unpack('V*', $this->sector($difat)));
            $difat = array_pop($ids);
            foreach ($ids as $id) if ($id !== self::FREE) $fatIds[] = $id;
        }
        if (count($fatIds) !== $fatCount || count(array_unique($fatIds)) !== $fatCount) $this->invalid();
        foreach ($fatIds as $id) {
            foreach (unpack('V*', $this->sector($id)) as $entry) $this->fat[] = $entry;
        }

        $directory = $this->chain($this->u32($bytes, 48), $this->fat);
        $entries = [];
        for ($offset = 0; $offset + 128 <= strlen($directory); $offset += 128) {
            $entry = substr($directory, $offset, 128);
            $type = ord($entry[66]);
            $entries[] = $entry;
        }
        if (!$entries || ord($entries[0][66]) !== 5) $this->invalid();
        $rootEntry = $entries[0];
        $root = ['start' => $this->u32($rootEntry, 116), 'size' => $this->u32($rootEntry, 120)];
        if ($this->u32($rootEntry, 124) !== 0 || $root['size'] > self::MAX_BYTES) $this->invalid();
        $streams = [];
        $pending = [$this->u32($rootEntry, 76)];
        $seen = [];
        while ($pending) {
            $id = array_pop($pending);
            if ($id === self::FREE) continue;
            if (isset($seen[$id]) || !isset($entries[$id])) $this->invalid();
            $seen[$id] = true;
            $entry = $entries[$id];
            $pending[] = $this->u32($entry, 68);
            $pending[] = $this->u32($entry, 72);
            // Embedded documents live in child storages; only read root-level streams.
            if (ord($entry[66]) !== 2) continue;
            $nameLength = $this->u16($entry, 64);
            if ($nameLength < 2 || $nameLength > 64 || $nameLength % 2) $this->invalid();
            $name = mb_convert_encoding(substr($entry, 0, $nameLength - 2), 'UTF-8', 'UTF-16LE');
            if ($this->u32($entry, 124) !== 0) $this->invalid();
            $stream = ['start' => $this->u32($entry, 116), 'size' => $this->u32($entry, 120)];
            if ($stream['size'] > self::MAX_BYTES) $this->invalid();
            $streams[$name] = $stream;
        }
        if (isset($streams['EncryptedPackage']) || isset($streams['EncryptionInfo'])) {
            throw new RuntimeException('This Word file is password-protected. Remove its password and upload it again.');
        }
        if (!$root || !isset($streams['WordDocument'])) $this->invalid();
        $miniFatCount = $this->u32($bytes, 64);
        if ($miniFatCount > $this->sectorCount) $this->invalid();
        if ($miniFatCount) {
            $miniFat = $this->chain($this->u32($bytes, 60), $this->fat, $miniFatCount * $this->sectorSize);
            $this->miniFat = array_values(unpack('V*', $miniFat));
            $this->miniStream = $this->chain($root['start'], $this->fat, $root['size']);
        }
        $word = $this->stream($streams['WordDocument']);
        if ($this->u16($word, 0) !== 0xa5ec || $this->u16($word, 2) < 0x00c1) {
            throw new RuntimeException('This older Word DOC format is unsupported. Save it as DOCX or PDF and upload it again.');
        }
        $flags = $this->u16($word, 10);
        if ($flags & 0x8100) {
            throw new RuntimeException('This DOC file is password-protected. Remove its password and upload it again.');
        }
        $tableName = ($flags & 0x0200) ? '1Table' : '0Table';
        if (!isset($streams[$tableName])) $this->invalid();
        $table = $this->stream($streams[$tableName]);

        // FIB is variable-length: locate FibRgLw and FibRgFcLcb via their counts.
        $longCountOffset = 34 + $this->u16($word, 32) * 2;
        $longCount = $this->u16($word, $longCountOffset);
        $longOffset = $longCountOffset + 2;
        if ($longCount < 4) $this->invalid();
        $mainCharacters = $this->u32($word, $longOffset + 12);
        if ($mainCharacters > self::MAX_BYTES / 2) $this->invalid();
        $pairCountOffset = $longOffset + $longCount * 4;
        if ($this->u16($word, $pairCountOffset) <= 33) $this->invalid();
        $clxPair = $pairCountOffset + 2 + 33 * 8;
        $clx = $this->slice($table, $this->u32($word, $clxPair), $this->u32($word, $clxPair + 4));
        $cursor = 0;
        while ($cursor < strlen($clx) && ord($clx[$cursor]) === 1) {
            $cursor += 3 + $this->u16($clx, $cursor + 1);
        }
        if ($cursor >= strlen($clx) || ord($clx[$cursor]) !== 2) $this->invalid();
        $plc = $this->slice($clx, $cursor + 5, $this->u32($clx, $cursor + 1));
        if (strlen($plc) < 4 || (strlen($plc) - 4) % 12) $this->invalid();
        $pieces = intdiv(strlen($plc) - 4, 12);
        if ($this->u32($plc, 0) !== 0) $this->invalid();
        $text = '';
        $lastCp = 0;
        for ($i = 0; $i < $pieces; $i++) {
            $start = $this->u32($plc, $i * 4);
            $end = $this->u32($plc, ($i + 1) * 4);
            if ($end < $start) $this->invalid();
            $lastCp = $end;
            if ($start >= $mainCharacters) continue;
            $characters = min($end, $mainCharacters) - $start;
            $fc = $this->u32($plc, ($pieces + 1) * 4 + $i * 8 + 2);
            $compressed = (bool) ($fc & 0x40000000);
            $position = $fc & 0x3fffffff;
            if ($compressed) {
                if ($position % 2) $this->invalid();
                $chunk = $this->slice($word, intdiv($position, 2), $characters);
                $text .= mb_convert_encoding($chunk, 'UTF-8', 'Windows-1252');
            } else {
                $chunk = $this->slice($word, $position, $characters * 2);
                $text .= mb_convert_encoding($chunk, 'UTF-8', 'UTF-16LE');
            }
        }
        if ($lastCp < $mainCharacters) $this->invalid();

        return $text;
    }

    private function stream(array $stream): string
    {
        return $stream['size'] < 4096
            ? $this->chain($stream['start'], $this->miniFat, $stream['size'], true)
            : $this->chain($stream['start'], $this->fat, $stream['size']);
    }

    private function chain(int $id, array $allocation, ?int $size = null, bool $mini = false): string
    {
        if ($size === 0) return '';
        $result = '';
        $seen = [];
        $unit = $mini ? 64 : $this->sectorSize;
        $limit = $mini ? intdiv(strlen($this->miniStream), 64) : $this->sectorCount;
        while ($id !== self::END) {
            if ($id >= $limit || isset($seen[$id]) || !isset($allocation[$id])) $this->invalid();
            $seen[$id] = true;
            $result .= $mini ? $this->slice($this->miniStream, $id * $unit, $unit) : $this->sector($id);
            if (strlen($result) > self::MAX_BYTES) $this->invalid();
            $id = $allocation[$id];
        }
        if ($size !== null) {
            if (strlen($result) < $size || strlen($result) - $size >= $unit) $this->invalid();
            return substr($result, 0, $size);
        }

        return $result;
    }

    private function sector(int $id): string
    {
        if ($id >= $this->sectorCount) $this->invalid();
        return $this->slice($this->bytes, ($id + 1) * $this->sectorSize, $this->sectorSize);
    }

    private function slice(string $data, int $offset, int $length): string
    {
        if ($offset < 0 || $length < 0 || $offset > strlen($data) || $length > strlen($data) - $offset) $this->invalid();
        return substr($data, $offset, $length);
    }

    private function u16(string $data, int $offset): int
    {
        return unpack('v', $this->slice($data, $offset, 2))[1];
    }

    private function u32(string $data, int $offset): int
    {
        return unpack('V', $this->slice($data, $offset, 4))[1];
    }

    private function cleanText(string $text): string
    {
        // Field instructions are not visible document text; retain their results.
        $visible = '';
        $fields = [];
        // Byte iteration preserves UTF-8 while avoiding a large per-character array.
        for ($i = 0, $length = strlen($text); $i < $length; $i++) {
            $character = $text[$i];
            if ($character === "\x13") { $fields[] = false; continue; }
            if ($character === "\x14" && $fields) { $fields[array_key_last($fields)] = true; continue; }
            if ($character === "\x15" && $fields) { array_pop($fields); continue; }
            if (!in_array(false, $fields, true)) $visible .= $character;
        }
        $visible = str_replace(["\r\n", "\r", "\x0b", "\x0c", "\x07"], ["\n", "\n", "\n", "\n", "\t"], $visible);
        return trim(preg_replace('/[\x00-\x08\x0e-\x1f]/u', '', $visible) ?? '');
    }

    private function invalid(): never
    {
        throw new RuntimeException('The DOC file is damaged or has an unsupported structure. Save a fresh copy as DOCX or PDF and upload it again.');
    }
}
