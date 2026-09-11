<?php
namespace App\Services\Ocr;
use App\Contracts\DocumentReader;
use App\Models\Assessment;
class ProductionDocumentReader implements DocumentReader {
 public function read(Assessment $assessment): array {
  throw new \RuntimeException('Production OCR is selected but no OCR provider adapter has been configured. Set EXAM_MARKER_OCR=demo or implement this adapter with your provider credentials.');
 }
}
