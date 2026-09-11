<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{Plan,User,Student,PaymentGatewaySetting};
class DatabaseSeeder extends Seeder {
 public function run():void{
  $base=['percentage'=>false,'grade'=>false,'graphs'=>false,'topic_analysis'=>false,'manual_mark_editing'=>false,'feedback_editing'=>false,'question_recheck'=>false,'result_report_download'=>false,'corrected_paper_download'=>false,'bulk_answer_upload'=>false,'bulk_student_import'=>false,'student_records'=>false,'parent_details'=>false,'parent_email'=>false,'parent_sms'=>false,'multiple_teachers'=>false,'teacher_assignment'=>false,'custom_branding'=>false,'student_portal'=>false,'parent_portal'=>false];
  $free=Plan::updateOrCreate(['slug'=>'free'],['name'=>'Free Mode','price'=>0,'billing_period'=>'lifetime','features'=>$base,'limits'=>['paper_check_limit'=>1,'student_limit'=>1,'teacher_limit'=>1,'email_limit'=>0,'sms_limit'=>0],'active'=>true]);
  $m1=Plan::updateOrCreate(['slug'=>'mode-1'],['name'=>'Mode 1 - Basic','price'=>399,'billing_period'=>'monthly','features'=>array_merge($base,['student_records'=>true]),'limits'=>['paper_check_limit'=>10,'student_limit'=>5,'teacher_limit'=>1,'email_limit'=>0,'sms_limit'=>0],'active'=>true]);
  $m2=Plan::updateOrCreate(['slug'=>'mode-2'],['name'=>'Mode 2 - Professional','price'=>999,'billing_period'=>'monthly','features'=>array_merge($base,['percentage'=>true,'grade'=>true,'graphs'=>true,'topic_analysis'=>true,'manual_mark_editing'=>true,'feedback_editing'=>true,'question_recheck'=>true,'result_report_download'=>true,'bulk_answer_upload'=>true,'bulk_student_import'=>true,'student_records'=>true]),'limits'=>['paper_check_limit'=>50,'student_limit'=>25,'teacher_limit'=>1,'email_limit'=>0,'sms_limit'=>0,'recheck_limit'=>100],'active'=>true]);
  $m3=Plan::updateOrCreate(['slug'=>'mode-3'],['name'=>'Mode 3 - Premium','price'=>1999,'billing_period'=>'monthly','features'=>array_merge($base,['percentage'=>true,'grade'=>true,'graphs'=>true,'topic_analysis'=>true,'manual_mark_editing'=>true,'feedback_editing'=>true,'question_recheck'=>true,'result_report_download'=>true,'corrected_paper_download'=>true,'bulk_answer_upload'=>true,'bulk_student_import'=>true,'student_records'=>true,'parent_details'=>true,'parent_email'=>true,'parent_sms'=>true,'multiple_teachers'=>true,'teacher_assignment'=>true]),'limits'=>['paper_check_limit'=>200,'student_limit'=>100,'teacher_limit'=>10,'email_limit'=>200,'sms_limit'=>200,'recheck_limit'=>500],'active'=>true]);


  foreach([
   ['provider'=>'paypal','label'=>'PayPal'],['provider'=>'payu','label'=>'PayU'],['provider'=>'phonepe','label'=>'PhonePe'],['provider'=>'paytm','label'=>'Paytm']
  ] as $gateway){PaymentGatewaySetting::updateOrCreate(['provider'=>$gateway['provider']],['label'=>$gateway['label'],'enabled'=>false,'environment'=>'sandbox','credentials'=>[],'options'=>[]]);}

  User::updateOrCreate(['email'=>'admin@example.com'],['name'=>'Platform Admin','mobile'=>'9000000001','password'=>'password','user_type'=>'coaching','is_admin'=>true,'plan_id'=>$m3->id,'organisation_name'=>'Exam Marker','account_status'=>'active']);
  User::updateOrCreate(['email'=>'free.teacher@example.com'],['name'=>'Free Teacher','mobile'=>'9000000002','password'=>'password','user_type'=>'teacher','plan_id'=>$free->id,'account_status'=>'active']);
  User::updateOrCreate(['email'=>'mode1@example.com'],['name'=>'Mode 1 User','mobile'=>'9000000003','password'=>'password','user_type'=>'teacher','plan_id'=>$m1->id,'account_status'=>'active']);
  User::updateOrCreate(['email'=>'mode2@example.com'],['name'=>'Mode 2 Teacher','mobile'=>'9000000004','password'=>'password','user_type'=>'teacher','plan_id'=>$m2->id,'account_status'=>'active']);
  $coach=User::updateOrCreate(['email'=>'mode3@example.com'],['name'=>'Mode 3 Coaching Admin','mobile'=>'9000000005','password'=>'password','user_type'=>'coaching','plan_id'=>$m3->id,'organisation_name'=>'Demo Coaching Class','account_status'=>'active']);
  $teacher=User::updateOrCreate(['email'=>'mode3.teacher@example.com'],['name'=>'Mode 3 Teacher','mobile'=>'9000000006','password'=>'password','user_type'=>'teacher','plan_id'=>$m3->id,'owner_user_id'=>$coach->id,'role_title'=>'Mathematics Teacher','account_status'=>'active']);
  $studentLogin=User::updateOrCreate(['email'=>'student@example.com'],['name'=>'Aarav Sharma','mobile'=>'9000000007','password'=>'password','user_type'=>'student','plan_id'=>$free->id,'account_status'=>'active']);
  Student::updateOrCreate(['user_id'=>$coach->id,'roll_number'=>'101'],['login_user_id'=>$studentLogin->id,'name'=>'Aarav Sharma','grade'=>'10','parent_name'=>'Rahul Sharma','parent_email'=>'parent@example.com','parent_mobile'=>'9876543210']);
 }
}
