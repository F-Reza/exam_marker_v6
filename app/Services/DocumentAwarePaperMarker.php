<?php
namespace App\Services;
use App\Contracts\DocumentReader;
use App\Models\Assessment;
class DocumentAwarePaperMarker implements PaperMarker {
 public function __construct(private DocumentReader $reader, private DemoPaperMarker $demo){}
 public function mark(Assessment $assessment): array {
  $documents=$this->reader->read($assessment);
  $payload=$this->demo->mark($assessment);
  $payload['summary']['document_reader']=$documents['source']??'unknown';
  return $payload;
 }
}
