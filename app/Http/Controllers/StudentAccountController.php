<?php
namespace App\Http\Controllers;
use App\Models\{Student,User,Plan,SystemSetting};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Mail,Password};
use Illuminate\Support\Str;
class StudentAccountController extends Controller {
 public function invite(Request $request,Student $student){
  abort_unless($request->user()->canAccessModule('students')&&($student->user_id===$request->user()->organisationOwnerId()||$request->user()->isPlatformAdmin()),403);
  abort_if($student->login_user_id,422,'This student already has a linked login.');abort_unless(SystemSetting::enabled('email_enabled',true),422,'Email delivery is disabled.');
  $data=$request->validate(['email'=>'required|email|max:150']);$email=strtolower($data['email']);$user=User::where('email',$email)->first();
  abort_if($user&&(!$user->isStudent()||Student::where('login_user_id',$user->id)->exists()),422,'That account is already linked or is not a student account.');
  $user??=User::create(['name'=>$student->name,'email'=>$email,'mobile'=>null,'password'=>Str::random(48),'user_type'=>'student','plan_id'=>Plan::where('slug','free')->value('id'),'account_status'=>'active']);
  $token=Str::random(64);DB::table('student_account_invitations')->where('student_id',$student->id)->whereNull('accepted_at')->delete();
  DB::table('student_account_invitations')->insert(['student_id'=>$student->id,'email'=>$email,'token_hash'=>hash('sha256',$token),'expires_at'=>now()->addDays(3),'created_at'=>now(),'updated_at'=>now()]);
  $url=route('students.invitation',['token'=>$token]);$reset=route('password.reset',['token'=>Password::createToken($user),'email'=>$email]);
  try{Mail::raw("You are invited to view published Exam Marker results for ".$student->name.".\nSet or reset your password: ".$reset."\nSign in with ".$email." and accept this invitation (expires in 3 days): ".$url,fn($mail)=>$mail->to($email)->subject('Exam Marker student invitation'));}
  catch(\Throwable $error){report($error);return back()->with('error','Invitation could not be emailed. Check email settings and resend.');}
  return back()->with('success','Student invitation sent. The account will link only after the recipient accepts it.');
 }
 public function show(Request $request,string $token){$invitation=$this->find($request,$token);$student=Student::findOrFail($invitation->student_id);return view('students.invitation',compact('student','token'));}
 public function accept(Request $request,string $token){
  DB::transaction(function()use($request,$token){$invitation=$this->find($request,$token,true);$student=Student::whereKey($invitation->student_id)->lockForUpdate()->firstOrFail();abort_if($student->login_user_id&&$student->login_user_id!==$request->user()->id,409,'Student already linked.');abort_if(Student::where('login_user_id',$request->user()->id)->where('id','!=',$student->id)->exists(),422,'Your account is already linked to another student record.');$student->update(['login_user_id'=>$request->user()->id]);$request->user()->forceFill(['email_verified_at'=>now()])->save();DB::table('student_account_invitations')->where('id',$invitation->id)->update(['accepted_at'=>now()]);});
  return redirect()->route('portal.student')->with('success','Student account linked. Published results will appear here.');
 }
 private function find(Request $request,string $token,bool $lock=false){$query=DB::table('student_account_invitations')->where('token_hash',hash('sha256',$token))->whereNull('accepted_at');if($lock)$query->lockForUpdate();$invitation=$query->first();abort_unless($invitation,404);abort_if(now()->gt($invitation->expires_at),410,'Invitation expired. Ask your teacher to resend it.');abort_unless($request->user()->isStudent()&&strtolower($request->user()->email)===$invitation->email,403,'Sign in with the invited student email address.');return$invitation;}
}
