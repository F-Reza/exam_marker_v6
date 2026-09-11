<?php
namespace App\Http\Controllers;
use App\Models\{Assessment,ParentCommunication,UsageRecord};
use App\Mail\ParentResultMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
class ParentCommunicationController extends Controller {
 public function send(Request $request,Assessment $assessment){
  abort_unless($request->user()->canAccessAssessment($assessment),403); abort_unless($request->user()->hasFeature('parent_email'),403); $assessment->load('student','results'); abort_unless(in_array($assessment->status,['finalised','reported']),422,'Finalise the result before sending it to a parent.'); abort_unless($assessment->student?->parent_email,422,'Parent email is missing.');
  $data=$request->validate(['message'=>'nullable|string|max:1000']); $this->enforceCommunicationLimit($request,'email'); $this->ensureLink($assessment);
  try{Mail::to($assessment->student->parent_email)->send(new ParentResultMail($assessment->fresh(),$data['message']??null));$this->log($assessment,$assessment->student->parent_email,'email','sent',$data['message']??null);return back()->with('success','Parent report email sent successfully.');}
  catch(\Throwable $e){report($e);$this->log($assessment,$assessment->student->parent_email,'email','failed',$e->getMessage());return back()->with('error','Email could not be sent. Check mail settings.');}
 }
 public function sendMobileLink(Request $request,Assessment $assessment){
  abort_unless($request->user()->canAccessAssessment($assessment),403); abort_unless($request->user()->hasFeature('parent_sms'),403); $assessment->load('student'); abort_unless(in_array($assessment->status,['finalised','reported']),422,'Finalise the result before sending it to a parent.'); abort_unless($assessment->student?->parent_mobile,422,'Parent mobile is missing.'); $this->ensureLink($assessment);
  $this->enforceCommunicationLimit($request,'mobile_link'); $url=route('parent.report.public',$assessment->public_report_token); $message='Exam Marker result: '.$url;
  if(!config('exam-marker.demo_sms',true)) return back()->with('error','Production SMS provider is not configured.');
  $this->log($assessment,$assessment->student->parent_mobile,'mobile_link','sent',$message); return back()->with('success','Demo mobile-link delivery recorded. Open Communications to view the secure link.');
 }
 private function enforceCommunicationLimit(Request $request,string $channel): void { $limitKey=$channel==='email'?'email_limit':'sms_limit'; $limit=$request->user()->limit($limitKey,0); if($limit<=0) return; $used=ParentCommunication::whereHas('assessment',fn($q)=>$q->where('user_id',$request->user()->organisationOwnerId()))->where('channel',$channel)->where('created_at','>=',now()->startOfMonth())->count(); abort_if($used>=$limit,422,'Parent communication limit reached for this billing cycle.'); }
 private function ensureLink(Assessment $a){if(!$a->public_report_token)$a->update(['public_report_token'=>Str::random(48),'public_report_expires_at'=>now()->addDays(config('exam-marker.parent_link_days',30)),'status'=>'reported']);}
 private function log(Assessment $a,string $recipient,string $channel,string $status,?string $message){ParentCommunication::create(['assessment_id'=>$a->id,'student_id'=>$a->student_id,'recipient'=>$recipient,'channel'=>$channel,'status'=>$status,'message'=>$message,'sent_at'=>$status==='sent'?now():null]);UsageRecord::create(['user_id'=>$a->user_id,'type'=>$channel==='email'?'parent_email':'parent_mobile_link','reference_type'=>Assessment::class,'reference_id'=>$a->id]);}
}
