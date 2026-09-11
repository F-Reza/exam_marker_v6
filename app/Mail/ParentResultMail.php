<?php
namespace App\Mail;
use App\Models\Assessment;
use App\Services\PdfReportService;
use Illuminate\Bus\Queueable;use Illuminate\Mail\Mailable;use Illuminate\Queue\SerializesModels;
class ParentResultMail extends Mailable {
 use Queueable,SerializesModels;
 public function __construct(public Assessment $assessment,public ?string $customMessage=null,public bool $attachPdf=true){}
 public function build(){
  $mail=$this->subject('Exam result: '.$this->assessment->title)->view('emails.parent-result');
  if($this->attachPdf){$pdf=app(PdfReportService::class)->make($this->assessment);$mail->attachData($pdf,'result-report-'.$this->assessment->id.'.pdf',['mime'=>'application/pdf']);}
  return $mail;
 }
}
