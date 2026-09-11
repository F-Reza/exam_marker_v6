<?php
namespace App\Services\Ocr;
use App\Contracts\DocumentReader;
use App\Models\Assessment;
class DemoDocumentReader implements DocumentReader {
 public function read(Assessment $assessment): array {
  return ['qp_text'=>'Demo extracted question-paper text','ms_text'=>$assessment->mark_scheme_path?'Demo extracted mark-scheme text':null,'wa_text'=>'Demo extracted written-answer text','source'=>'demo'];
 }
}
