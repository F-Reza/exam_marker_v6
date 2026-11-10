<?php
namespace App\Services;
use App\Models\Assessment;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
class PdfReportService {
 public function make(Assessment $assessment):string{
  $directory=storage_path('framework/pdf');if(!is_dir($directory))mkdir($directory,0700,true);
  $pdf=new Mpdf(['mode'=>'utf-8','format'=>'A4','default_font'=>'freeserif','default_font_size'=>11,'tempDir'=>$directory,'margin_top'=>18,'margin_bottom'=>18,'autoScriptToLang'=>true,'autoLangToFont'=>true]);
  $pdf->SetTitle($assessment->title.' - Exam Marker');$pdf->SetAuthor('Exam Marker');$pdf->SetFooter('Exam Marker||{PAGENO} / {nbpg}');
  $html=view('reports.print-body',app(ReportPresenter::class)->data($assessment))->render();
  $pdf->WriteHTML('<style>body{font-family:freeserif;color:#17243a;line-height:1.5}h1{font-size:22pt;color:#1855a5}h2{font-size:15pt;margin-top:20pt}h3{font-size:12pt;margin-bottom:4pt}p{margin:6pt 0}.question-result{border-bottom:1px solid #dbe3ed;padding-bottom:10pt}li{margin-bottom:4pt}</style>'.$html);
  return $pdf->Output('',Destination::STRING_RETURN);
 }
}
