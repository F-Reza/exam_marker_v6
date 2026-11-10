<?php
namespace App\Http\Controllers;
use App\Models\{Assessment,ParentCommunication,UsageRecord,User,SystemSetting};
use App\Mail\ParentResultMail;
use App\Services\{LifecycleNotifier,SubscriptionService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Mail};
use Illuminate\Support\Str;
class ParentCommunicationController extends Controller {
 public function send(Request $request,Assessment $assessment){return $this->deliver($request,$assessment,'email');}
 public function sendMobileLink(Request $request,Assessment $assessment){return $this->deliver($request,$assessment,'mobile_link');}
 private function deliver(Request $request,Assessment $assessment,string $channel){
  $email=$channel==='email';abort_unless($request->user()->canAccessAssessment($assessment)&&$request->user()->canAccessModule($email?'parent-email':'parent-sms'),403);
  abort_unless(SystemSetting::enabled($email?'email_enabled':'sms_enabled',true),422,'This delivery channel is disabled by the administrator.');
  abort_unless(in_array($assessment->status,['finalised','reported'],true),422,'Finalise the result before sending it.');
  $assessment->load('student','results');$recipient=$email?$assessment->student?->parent_email:$assessment->student?->parent_mobile;abort_unless($recipient,422,$email?'Parent email is missing.':'Parent mobile is missing.');
  $data=$request->validate(['message'=>'nullable|string|max:1000']);
  if(!$email&&!config('exam-marker.demo_sms',true))return back()->with('error','Production SMS delivery is not configured.');
  $log=DB::transaction(function()use($assessment,$request,$channel,$email,$recipient,$data){
   $owner=User::whereKey($assessment->user_id)->lockForUpdate()->firstOrFail();$type=$email?'parent_email':'parent_mobile_link';$limit=$owner->limit($email?'email_limit':'sms_limit',0);
   $used=UsageRecord::where('user_id',$owner->id)->where('type',$type)->where('created_at','>=',app(SubscriptionService::class)->cycleStart($owner))->sum('quantity');
   abort_if(!$request->user()->isPlatformAdmin()&&($limit<=0||$used>=$limit),422,'Parent communication limit reached for this billing cycle.');
   $paper=Assessment::whereKey($assessment->id)->lockForUpdate()->firstOrFail();abort_unless(in_array($paper->status,['finalised','reported'],true),422);
   if(!$paper->public_report_token||!$paper->public_report_expires_at||$paper->public_report_expires_at->isPast())$paper->update(['public_report_token'=>Str::random(48),'public_report_expires_at'=>now()->addDays(config('exam-marker.parent_link_days',30))]);
   UsageRecord::create(['user_id'=>$owner->id,'type'=>$type,'reference_type'=>Assessment::class,'reference_id'=>$assessment->id]);
   return ParentCommunication::create(['assessment_id'=>$paper->id,'student_id'=>$paper->student_id,'recipient'=>$recipient,'channel'=>$channel,'status'=>'pending','message'=>$data['message']??null]);
  });
  try{
   $assessment->refresh();$message=$data['message']??null;
   if($email)Mail::to($recipient)->send(new ParentResultMail($assessment,$message));
   else $message='Demo delivery: '.route('parent.report.public',$assessment->public_report_token);
   $log->update(['status'=>'sent','message'=>$message,'sent_at'=>now()]);
   Assessment::whereKey($assessment->id)->whereIn('status',['finalised','reported'])->update(['status'=>'reported']);
   app(LifecycleNotifier::class)->send($assessment->user,'parent_delivery','Parent report delivery recorded',$email?'The report email was sent.':'The demo mobile secure link was recorded.',route('communications.index'));
   return back()->with('success',$email?'Parent report email sent.':'Demo mobile link recorded in Communications.');
  }catch(\Throwable $error){report($error);$log->update(['status'=>'failed','message'=>'Delivery failed. Check server mail settings.']);return back()->with('error','Email could not be sent. Check mail settings and retry.');}
 }
}