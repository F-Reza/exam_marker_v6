<?php
namespace App\Http\Controllers;
use App\Models\Assessment;
use App\Services\PdfReportService;
class PublicReportController extends Controller {
 private function find(string $token): Assessment { $a=Assessment::where('public_report_token',$token)->with('student','results','user')->firstOrFail();abort_if($a->public_report_expires_at && now()->greaterThan($a->public_report_expires_at),410,'This report link has expired.');abort_unless(in_array($a->status,['finalised','reported']),404);return $a; }
 public function show(string $token){$assessment=$this->find($token);return view('parent.report',compact('assessment'));}
 public function pdf(string $token,PdfReportService $pdf){$assessment=$this->find($token);return response($pdf->make($assessment),200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="student-result-'.$assessment->id.'.pdf"']);}
}
