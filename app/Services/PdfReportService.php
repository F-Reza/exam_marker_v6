<?php
namespace App\Services;
use App\Models\Assessment;
class PdfReportService {
 public function make(Assessment $a): string {
  $a->loadMissing('results','student');
  $lines=['EXAM MARKER - RESULT REPORT','Assessment: '.$a->title,'Student: '.($a->student?->name??'Individual User'),'Subject: '.$a->subject,'Grade/Class: '.($a->grade??'-'),'','QUESTION-WISE MARKS'];
  foreach($a->results as $r)$lines[]=$r->question_number.'  '.($r->teacher_marks??$r->ai_marks).' / '.$r->max_marks.'  '.preg_replace('/\s+/',' ',(string)$r->feedback);
  $obt=$a->results->sum(fn($r)=>(float)($r->teacher_marks??$r->ai_marks));$max=$a->results->sum(fn($r)=>(float)$r->max_marks);
  $lines[]='';$lines[]='Total: '.$obt.' / '.$max;$lines[]='Percentage: '.($a->percentage??round($obt/max(1,$max)*100,1)).'%';$lines[]='Grade: '.($a->grade_awarded??'-');
  return $this->simplePdf($lines);
 }
 private function simplePdf(array $lines): string {
  $pages=array_chunk($lines,42);$objects=[];$pageIds=[];$fontId=3;$nextId=4;
  foreach($pages as $page){$pageId=$nextId++;$contentId=$nextId++;$pageIds[]=$pageId;$stream="BT /F1 11 Tf 48 790 Td 14 TL\n";foreach($page as $line){$safe=str_replace(['\\','(',')'],['\\\\','\\(','\\)'],mb_substr((string)$line,0,110));$stream.='('.$safe.") Tj T*\n";}$stream.="ET";$objects[$contentId]="<< /Length ".strlen($stream)." >>\nstream\n$stream\nendstream";$objects[$pageId]="<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 $fontId 0 R >> >> /Contents $contentId 0 R >>";}
  $objects[1]='<< /Type /Catalog /Pages 2 0 R >>';$objects[2]='<< /Type /Pages /Kids ['.implode(' ',array_map(fn($id)=>$id.' 0 R',$pageIds)).'] /Count '.count($pageIds).' >>';$objects[3]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';ksort($objects);
  $pdf="%PDF-1.4\n";$offsets=[0=>0];foreach($objects as $id=>$body){$offsets[$id]=strlen($pdf);$pdf.="$id 0 obj\n$body\nendobj\n";}$xref=strlen($pdf);$max=max(array_keys($objects));$pdf.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";for($i=1;$i<=$max;$i++)$pdf.=sprintf("%010d 00000 n \n",$offsets[$i]??0);$pdf.="trailer\n<< /Size ".($max+1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";return $pdf;
 }
}
