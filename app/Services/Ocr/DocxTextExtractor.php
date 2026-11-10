<?php

namespace App\Services\Ocr;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/** Reads text and list labels from Word OOXML without invoking Microsoft Office. */
class DocxTextExtractor
{
    private const WORD_NAMESPACES = [
        'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
        'http://purl.oclc.org/ooxml/wordprocessingml/main',
    ];

    public function extract(string $file): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('DOCX reading requires the PHP ZIP extension. Enable it and try again.');
        }

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            throw new RuntimeException('The DOCX file is damaged or password-protected. Save an unprotected copy in Word and upload it again.');
        }

        try {
            $document = $this->xml($this->part($zip, 'word/document.xml', true));
            $numberingXml = $this->part($zip, 'word/numbering.xml');
            $stylesXml = $this->part($zip, 'word/styles.xml');
            [$numbering, $instances] = $numberingXml === null ? [[], []] : $this->numbering($this->xml($numberingXml));
            $styles = $stylesXml === null ? [] : $this->styles($this->xml($stylesXml));
            $counters = [];
            $xpath = $this->xpath($document);
            $body = $xpath->query('/w:document/w:body')->item(0);
            if (!$body) {
                throw new RuntimeException('The DOCX file does not contain a valid Word document body. Save it as a new DOCX file and try again.');
            }
            $text = trim($this->blocks($body, $numbering, $instances, $styles, $counters));
        } finally {
            $zip->close();
        }

        if ($text === '') {
            throw new RuntimeException('The DOCX file contains no readable text. If it contains scanned pages or photographs, export it as a PDF for OCR and upload that PDF.');
        }

        return $text;
    }

    private function part(ZipArchive $zip, string $name, bool $required = false): ?string
    {
        $stat = $zip->statName($name);
        if ($stat === false) {
            if ($required) {
                throw new RuntimeException('This file is not a valid DOCX document. Open it in Word and save it as DOCX before uploading.');
            }
            return null;
        }
        if (($stat['encryption_method'] ?? 0) !== 0) {
            throw new RuntimeException('The DOCX file is password-protected. Remove its password before uploading.');
        }
        if ($stat['size'] > 64 * 1024 * 1024) {
            throw new RuntimeException('The DOCX document is too large to read. Split it into smaller documents and try again.');
        }
        $xml = $zip->getFromName($name);
        if ($xml === false) {
            throw new RuntimeException('The DOCX file cannot be read. Save a new unprotected DOCX copy and try again.');
        }
        return $xml;
    }

    private function xml(string $xml): DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            if (stripos($xml, '<!DOCTYPE') !== false || !$document->loadXML($xml, LIBXML_NONET)) {
                throw new RuntimeException('The DOCX file contains invalid document XML. Save a new copy in Word and upload it again.');
            }
            return $document;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function xpath(DOMDocument $document): DOMXPath
    {
        $namespace = $document->documentElement?->namespaceURI;
        if (!in_array($namespace, self::WORD_NAMESPACES, true)) {
            throw new RuntimeException('The DOCX file has an unsupported document structure. Save a new DOCX copy in Word and try again.');
        }
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', $namespace);
        return $xpath;
    }

    private function blocks(DOMNode $parent, array $definitions, array $instances, array $styles, array &$counters): string
    {
        $text = '';
        foreach ($parent->childNodes as $node) {
            if (!$node instanceof DOMElement || $this->excluded($node)) continue;
            if ($this->isWord($node, 'p')) {
                $content = $this->inline($node);
                if (trim($content) !== '') {
                    $label = $this->listLabel($node, $definitions, $instances, $styles, $counters);
                    $text .= $label.$content."\n";
                }
            } elseif ($this->isWord($node, 'tc')) {
                $text .= rtrim($this->blocks($node, $definitions, $instances, $styles, $counters))."\t";
            } elseif ($this->isWord($node, 'tr')) {
                $text .= rtrim($this->blocks($node, $definitions, $instances, $styles, $counters))."\n";
            } else {
                $text .= $this->blocks($node, $definitions, $instances, $styles, $counters);
            }
        }
        return $text;
    }

    private function inline(DOMNode $parent): string
    {
        $text = '';
        foreach ($parent->childNodes as $node) {
            if (!$node instanceof DOMElement || $this->excluded($node)) continue;
            if ($this->isWord($node, 't')) $text .= $node->textContent;
            elseif ($this->isWord($node, 'tab')) $text .= "\t";
            elseif ($this->isWord($node, 'br') || $this->isWord($node, 'cr')) $text .= "\n";
            elseif ($this->isWord($node, 'noBreakHyphen')) $text .= '-';
            elseif (!$this->isWord($node, 'pPr') && !$this->isWord($node, 'rPr')) $text .= $this->inline($node);
        }
        return $text;
    }

    private function excluded(DOMElement $node): bool
    {
        if ($this->isWord($node, 'del') || $this->isWord($node, 'moveFrom') || $this->isWord($node, 'instrText')) return true;
        if ($this->isWord($node, 'r')) {
            $xpath = $this->xpath($node->ownerDocument);
            foreach ($xpath->query('./w:rPr/w:vanish | ./w:rPr/w:webHidden', $node) as $hidden) {
                if (!in_array($this->value($hidden), ['0', 'false', 'off'], true)) return true;
            }
        }
        return false;
    }

    private function isWord(DOMElement $node, string $name): bool
    {
        return $node->localName === $name && in_array($node->namespaceURI, self::WORD_NAMESPACES, true);
    }

    private function value(DOMElement $element, string $name = 'val'): string
    {
        return $element->getAttributeNS($element->namespaceURI, $name);
    }

    private function numbering(DOMDocument $document): array
    {
        $xpath = $this->xpath($document);
        $definitions = $instances = [];
        foreach ($xpath->query('/w:numbering/w:abstractNum') as $abstract) {
            $id = $this->value($abstract, 'abstractNumId');
            foreach ($xpath->query('./w:lvl', $abstract) as $level) {
                $definitions[$id][(int) $this->value($level, 'ilvl')] = $this->level($level, $xpath);
            }
        }
        foreach ($xpath->query('/w:numbering/w:num') as $num) {
            $id = $this->value($num, 'numId');
            $abstract = $xpath->query('./w:abstractNumId', $num)->item(0);
            if (!$abstract) continue;
            $instances[$id] = ['abstract' => $this->value($abstract), 'overrides' => []];
            foreach ($xpath->query('./w:lvlOverride', $num) as $override) {
                $level = (int) $this->value($override, 'ilvl');
                $replacement = $xpath->query('./w:lvl', $override)->item(0);
                $start = $xpath->query('./w:startOverride', $override)->item(0);
                if ($replacement) $instances[$id]['overrides'][$level] = $this->level($replacement, $xpath);
                if ($start) $instances[$id]['overrides'][$level]['start'] = (int) $this->value($start);
            }
        }
        return [$definitions, $instances];
    }

    private function level(DOMElement $level, DOMXPath $xpath): array
    {
        $result = ['start' => 1, 'format' => 'decimal', 'text' => '%'.((int) $this->value($level, 'ilvl') + 1).'.'];
        foreach (['start' => 'start', 'numFmt' => 'format', 'lvlText' => 'text'] as $element => $key) {
            $child = $xpath->query('./w:'.$element, $level)->item(0);
            if ($child) $result[$key] = $key === 'start' ? (int) $this->value($child) : $this->value($child);
        }
        return $result;
    }

    private function styles(DOMDocument $document): array
    {
        $xpath = $this->xpath($document);
        $styles = [];
        foreach ($xpath->query('/w:styles/w:style') as $style) {
            $styles[$this->value($style, 'styleId')] = [
                'numbering' => $this->paragraphNumbering($style, $xpath),
                'parent' => ($parent = $xpath->query('./w:basedOn', $style)->item(0)) ? $this->value($parent) : null,
            ];
        }
        return $styles;
    }

    private function paragraphNumbering(DOMElement $paragraph, DOMXPath $xpath): array
    {
        $result = [];
        foreach (['numId' => 'id', 'ilvl' => 'level'] as $name => $key) {
            $element = $xpath->query('./w:pPr/w:numPr/w:'.$name, $paragraph)->item(0);
            if ($element) $result[$key] = $this->value($element);
        }
        return $result;
    }

    private function listLabel(DOMElement $paragraph, array $definitions, array $instances, array $styles, array &$counters): string
    {
        $xpath = $this->xpath($paragraph->ownerDocument);
        $numbering = $this->paragraphNumbering($paragraph, $xpath);
        $style = $xpath->query('./w:pPr/w:pStyle', $paragraph)->item(0);
        $styleId = $style ? $this->value($style) : null;
        $visited = [];
        while ($styleId !== null && isset($styles[$styleId]) && !isset($visited[$styleId])) {
            $visited[$styleId] = true;
            $numbering += $styles[$styleId]['numbering'];
            $styleId = $styles[$styleId]['parent'];
        }
        $id = $numbering['id'] ?? '0';
        if ($id === '0' || !isset($instances[$id])) return '';
        $level = (int) ($numbering['level'] ?? 0);
        $levels = $definitions[$instances[$id]['abstract']] ?? [];
        foreach ($instances[$id]['overrides'] as $index => $override) $levels[$index] = array_replace($levels[$index] ?? [], $override);
        if (!isset($levels[$level])) return '';
        $definition = $levels[$level];
        $counters[$id][$level] = isset($counters[$id][$level]) ? $counters[$id][$level] + 1 : $definition['start'];
        foreach (array_keys($counters[$id]) as $index) if ($index > $level) unset($counters[$id][$index]);
        if ($definition['format'] === 'bullet') return $definition['text'].' ';
        if ($definition['format'] === 'none') return '';
        $label = preg_replace_callback('/%([1-9])/', function ($match) use ($id, $levels, &$counters) {
            $index = (int) $match[1] - 1;
            $value = $counters[$id][$index] ?? ($levels[$index]['start'] ?? 1);
            return $this->format($value, $levels[$index]['format'] ?? 'decimal');
        }, $definition['text']);
        return $label.' ';
    }

    private function format(int $value, string $format): string
    {
        if (in_array($format, ['lowerLetter', 'upperLetter'], true)) {
            $text = '';
            while ($value > 0) {
                $value--;
                $text = chr(97 + $value % 26).$text;
                $value = intdiv($value, 26);
            }
            return $format === 'upperLetter' ? strtoupper($text) : $text;
        }
        if (in_array($format, ['lowerRoman', 'upperRoman'], true) && $value > 0 && $value < 4000) {
            $text = '';
            foreach ([1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD', 100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL', 10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I'] as $number => $letter) {
                while ($value >= $number) { $text .= $letter; $value -= $number; }
            }
            return $format === 'lowerRoman' ? strtolower($text) : $text;
        }
        return (string) $value;
    }
}
