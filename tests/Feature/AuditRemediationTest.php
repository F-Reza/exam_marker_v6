<?php
namespace Tests\Feature;

use App\Contracts\DocumentReader;
use App\Jobs\ProcessAssessment;
use App\Models\{Assessment,AiSetting,PaymentGatewaySetting,PaymentTransaction,Plan,Student,SystemSetting,UsageRecord,User};
use App\Services\{AdaptivePaperMarker,AIPaperMarker,AssessmentProcessingService,GradingService,LifecycleNotifier,MarkingPayloadValidator,PaperMarker,PdfReportService,QuotaService,QuestionStructure,SubscriptionService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB,Http,Mail,Notification,Storage};
use Tests\TestCase;

class AuditRemediationTest extends TestCase
{
 use RefreshDatabase;
 protected function setUp():void{parent::setUp();$this->withoutVite();$this->seed();Storage::fake('local');Http::preventStrayRequests();config(['logging.default'=>'null','exam-marker.async'=>false]);}
 private function user(string $mode='mode2'):User{return User::where('email',$mode.'@example.com')->firstOrFail();}
 private function paper(User $user,array $extra=[]):Assessment{return Assessment::create($extra+['user_id'=>$user->id,'title'=>'Audit regression','subject'=>'Physics','total_marks'=>10,'status'=>'uploaded','question_paper_path'=>'test/q.pdf','written_answer_path'=>'test/a.docx']);}
 private function files():array{return ['title'=>'Quota paper','subject'=>'Physics','total_marks'=>10,'question_paper'=>UploadedFile::fake()->createWithContent('question.pdf',file_get_contents(__DIR__.'/../Fixtures/documents/question.pdf')),'written_answer'=>UploadedFile::fake()->createWithContent('answer.docx',file_get_contents(__DIR__.'/../Fixtures/documents/answer.docx'))];}
 private function row(array $extra=[]):array{return $extra+['question_number'=>'1','max_marks'=>10,'ai_marks'=>7,'confidence'=>'high','feedback'=>'Teacher review required.'];}

 public function test_unpaid_upgrade_and_forged_cross_gateway_callback_cannot_grant_access():void{
  $user=$this->user('free.teacher');$plan=Plan::where('slug','mode-3')->firstOrFail();$this->actingAs($user)->post(route('billing.change'),['plan_id'=>$plan->id])->assertRedirect(route('payments.checkout',$plan));$this->assertNotSame($plan->id,$user->fresh()->plan_id);
  $tx=PaymentTransaction::create(['user_id'=>$user->id,'plan_id'=>$plan->id,'gateway'=>'paypal','reference'=>'FORGED','provider_reference'=>'O-1','amount'=>1999,'currency'=>'USD','status'=>'pending']);
  $this->post(route('payments.callback',['provider'=>'payu','tx'=>$tx->reference]),['status'=>'success'])->assertUnprocessable();$this->assertSame('pending',$tx->fresh()->status);Http::assertNothingSent();
 }
 public function test_verified_payment_is_bound_and_replay_safe_and_cancel_preserves_paid_status():void{
  $user=$this->user('free.teacher');$plan=Plan::where('slug','mode-2')->firstOrFail();PaymentGatewaySetting::where('provider','paypal')->firstOrFail()->update(['enabled'=>true,'credentials'=>['client_id'=>'test','client_secret'=>'secret']]);
  $tx=PaymentTransaction::create(['user_id'=>$user->id,'plan_id'=>$plan->id,'gateway'=>'paypal','reference'=>'VERIFIED','provider_reference'=>'O-1','amount'=>999,'currency'=>'USD','status'=>'pending']);
  Http::fake(['*/v1/oauth2/token'=>Http::response(['access_token'=>'provider-token']),'*/v2/checkout/orders/O-1'=>Http::response(['id'=>'O-1','status'=>'APPROVED','purchase_units'=>[['reference_id'=>'VERIFIED','amount'=>['currency_code'=>'USD','value'=>'999.00']]]]),'*/O-1/capture'=>Http::response(['id'=>'O-1','status'=>'COMPLETED','purchase_units'=>[['payments'=>['captures'=>[['status'=>'COMPLETED','amount'=>['currency_code'=>'USD','value'=>'999.00']]]]]]])]);
  $url=route('payments.callback',['provider'=>'paypal','tx'=>$tx->reference]);$this->get($url.'?token=O-1')->assertRedirect(route('billing.index'));$this->assertSame('paid',$tx->fresh()->status);$this->assertSame($plan->id,$user->fresh()->plan_id);$this->assertTrue($user->fresh()->subscription_ends_at->isFuture());
  $count=count(Http::recorded());$this->get($url.'?token=O-1')->assertRedirect();$this->assertCount($count,Http::recorded());$this->actingAs($user)->get(route('payments.cancel',$tx->reference))->assertRedirect();$this->assertSame('paid',$tx->fresh()->status);
 }
 public function test_provider_amount_mismatch_keeps_transaction_pending_and_plan_unchanged():void{
  $user=$this->user('free.teacher');$old=$user->plan_id;$plan=Plan::where('slug','mode-3')->firstOrFail();PaymentGatewaySetting::where('provider','paypal')->firstOrFail()->update(['credentials'=>['client_id'=>'test','client_secret'=>'secret']]);
  $tx=PaymentTransaction::create(['user_id'=>$user->id,'plan_id'=>$plan->id,'gateway'=>'paypal','reference'=>'BAD-AMOUNT','provider_reference'=>'O-2','amount'=>1999,'currency'=>'USD','status'=>'pending']);
  Http::fake(['*/v1/oauth2/token'=>Http::response(['access_token'=>'token']),'*/orders/O-2'=>Http::response(['id'=>'O-2','status'=>'APPROVED','purchase_units'=>[['reference_id'=>'BAD-AMOUNT','amount'=>['currency_code'=>'USD','value'=>'1.00']]]])]);
  $this->get(route('payments.callback',['provider'=>'paypal','tx'=>$tx->reference]).'?token=O-2')->assertRedirect()->assertSessionHas('error');$this->assertSame('pending',$tx->fresh()->status);$this->assertSame($old,$user->fresh()->plan_id);Http::assertSentCount(2);
 }
 public function test_printed_maxima_and_subquestion_variants_match_without_synthetic_questions():void{
  $structure=new QuestionStructure();$questions=$structure->parse("1(a) Explain force [2]\n(b) Explain energy [8]",10);$this->assertSame([2.0,8.0],array_column($questions,'marks'));
  $answers=$structure->sections("1 a Force is a push or pull.\n1b Energy is the capacity to do work.",array_column($questions,'label'));$this->assertStringContainsString('push',$answers['1(a)']);$this->assertStringContainsString('capacity',$answers['1(b)']);
  $this->expectException(\RuntimeException::class);$structure->parse('No question numbers are readable.',10);
 }
 public function test_payu_requires_response_signature_and_server_verification_before_activation():void{
  $user=$this->user('free.teacher');$plan=Plan::where('slug','mode-2')->firstOrFail();$gateway=PaymentGatewaySetting::where('provider','payu')->firstOrFail();$gateway->update(['enabled'=>true,'credentials'=>['merchant_key'=>'merchant','salt'=>'server-salt']]);
  $tx=PaymentTransaction::create(['user_id'=>$user->id,'plan_id'=>$plan->id,'gateway'=>'payu','reference'=>'PAYU-ORDER','provider_reference'=>'PAYU-ORDER','amount'=>999,'currency'=>'INR','status'=>'pending']);
  $url=route('payments.callback',['provider'=>'payu','tx'=>$tx->reference]);$payload=['key'=>'merchant','txnid'=>'PAYU-ORDER','amount'=>'999.00','firstname'=>'Student','email'=>$user->email,'productinfo'=>'Mode 2','status'=>'success'];
  $this->post($url,$payload)->assertRedirect()->assertSessionHas('error');$this->assertSame('pending',$tx->fresh()->status);Http::assertNothingSent();
  $payload['hash']=hash('sha512','server-salt|success|||||||||||'.$user->email.'|Student|Mode 2|999.00|PAYU-ORDER|merchant');
  Http::fake(['https://test.payu.in/merchant/postservice*'=>Http::response(['status'=>1,'transaction_details'=>['PAYU-ORDER'=>['txnid'=>'PAYU-ORDER','transaction_amount'=>'999.00','status'=>'success','unmappedstatus'=>'captured','mihpayid'=>'provider-id']]])]);
  $this->post($url,$payload)->assertRedirect()->assertSessionHas('success');$this->assertSame('paid',$tx->fresh()->status);$this->assertSame($plan->id,$user->fresh()->plan_id);Http::assertSentCount(1);
 }
 public function test_invalid_marking_payloads_are_rejected_and_valid_rubric_is_persisted():void{
  $paper=$this->paper($this->user());$validator=new MarkingPayloadValidator();
  foreach([[],[$this->row(['ai_marks'=>20])],[$this->row(['max_marks'=>5]),$this->row(['max_marks'=>5])],[$this->row(['ai_marks'=>-1])]] as $rows){try{$validator->validate($paper,['questions'=>$rows]);$this->fail('Invalid payload accepted');}catch(\RuntimeException){$this->addToAssertionCount(1);}}
  $row=$this->row(['type'=>'writing','writing_rubric'=>['content'=>['awarded'=>7,'max_marks'=>10,'feedback'=>'Complete']]]);$this->app->instance(PaperMarker::class,new class($row)implements PaperMarker{public function __construct(private array $row){}public function mark(Assessment $a):array{return['questions'=>[$this->row]];}});
  app(AssessmentProcessingService::class)->start($paper);$result=$paper->results()->firstOrFail();$this->assertSame(7,$result->writing_rubric['content']['awarded']);$this->assertSame('70.00',$paper->fresh()->percentage);
  $result->update(['teacher_marks'=>9]);$paper->update(['status'=>'finalised','finalised_at'=>now()]);(new ProcessAssessment($paper->id,$paper->fresh()->processing_token))->handle(app(PaperMarker::class),app(\App\Services\AuditService::class));$this->assertSame('9.00',$result->fresh()->teacher_marks);$this->assertSame(1,UsageRecord::where('type','paper_processed')->count());
 }
 public function test_database_ai_configuration_and_insert_context_control_outgoing_request():void{
  AiSetting::create(['provider'=>'gemini','model'=>'selected-model','api_key'=>'test-key','status'=>'active']);config(['ai.provider'=>'openai']);$paper=$this->paper($this->user());
  $this->app->instance(DocumentReader::class,new class implements DocumentReader{public function read(Assessment $a):array{return['qp_text'=>'1 Explain force [10]','ms_text'=>'1 Force is a push or pull [10]','wa_text'=>'1 A force is a push or pull.','insert_text'=>'UNIQUE_SOURCE_MARKER'];}});
  Http::fake(['*'=>Http::response(['candidates'=>[['content'=>['parts'=>[['text'=>json_encode(['questions'=>[$this->row()]])]]]]]])]);$payload=app(AIPaperMarker::class)->mark($paper);$this->assertSame(70.0,$payload['percentage']);
  Http::assertSent(fn($request)=>str_contains($request->url(),'selected-model:generateContent')&&$request->hasHeader('x-goog-api-key','test-key')&&str_contains(json_encode($request->data()),'UNIQUE_SOURCE_MARKER'));
 }
 public function test_targeted_ai_recheck_omits_other_answers_and_preserves_authoritative_marks():void{
  AiSetting::create(['provider'=>'openai','model'=>'selected-model','api_key'=>'test-key','status'=>'active']);$paper=$this->paper($this->user());$target=$paper->results()->create($this->row(['question_part'=>'a','max_marks'=>2,'ai_marks'=>1]));
  $this->app->instance(DocumentReader::class,new class implements DocumentReader{public function read(Assessment $a):array{return['qp_text'=>"1(a) Force [2]\n(b) Energy [8]",'ms_text'=>"1(a) Push or pull [2]\n(b) Work [8]",'wa_text'=>"1(a) SELECTED_ANSWER push pull\n(b) UNRELATED_ANSWER energy",'insert_text'=>'SOURCE_CONTEXT'];}});
  Http::fake(['*'=>Http::response(['choices'=>[['message'=>['content'=>json_encode(['questions'=>[$this->row(['question_part'=>'a','max_marks'=>2,'ai_marks'=>1])]])]]]])]);
  $row=app(AIPaperMarker::class)->markQuestion($paper,$target);$this->assertSame(2.0,$row['max_marks']);Http::assertSent(fn($request)=>str_contains(json_encode($request->data()),'SELECTED_ANSWER')&&!str_contains(json_encode($request->data()),'UNRELATED_ANSWER'));
 }
 public function test_exhausted_api_credit_has_an_actionable_error_instead_of_a_null_response():void{
  AiSetting::create(['provider'=>'openai','model'=>'selected-model','api_key'=>'test-key','status'=>'active']);$paper=$this->paper($this->user());
  $this->app->instance(DocumentReader::class,new class implements DocumentReader{public function read(Assessment $a):array{return['qp_text'=>'1 Explain force [10]','ms_text'=>null,'wa_text'=>'1 Force is a push.','insert_text'=>''];}});
  Http::fake(['*'=>Http::response(['error'=>['code'=>'insufficient_quota']],429)]);$this->expectException(\RuntimeException::class);$this->expectExceptionMessage('no available API credit or quota');app(AIPaperMarker::class)->mark($paper);
 }
 public function test_database_queue_executes_once_and_stale_attempt_recovery_invalidates_old_jobs():void{
  $paper=$this->paper($this->user());$row=$this->row();$this->app->instance(PaperMarker::class,new class($row)implements PaperMarker{public function __construct(private array $row){}public function mark(Assessment $a):array{return['questions'=>[$this->row]];}});
  config(['exam-marker.async'=>true,'queue.default'=>'database','queue.connections.database.after_commit'=>false]);app(AssessmentProcessingService::class)->start($paper);$this->assertSame(1,DB::table('jobs')->count());
  $this->artisan('queue:work',['connection'=>'database','--once'=>true,'--sleep'=>0])->assertSuccessful();$this->assertSame('review_required',$paper->fresh()->status);$this->assertSame(0,DB::table('jobs')->count());
  $stale=$this->paper($this->user(),['status'=>'processing','processing_token'=>'old-token','processing_started_at'=>now()->subMinutes(11)]);$this->artisan('assessments:recover-stalled')->assertSuccessful();$this->assertSame('failed',$stale->fresh()->status);$this->assertNotSame('old-token',$stale->fresh()->processing_token);
  (new ProcessAssessment($stale->id,'old-token'))->handle(app(PaperMarker::class),app(\App\Services\AuditService::class));$this->assertSame(0,$stale->results()->count());
 }
 public function test_grade_stays_consistent_through_edit_finalise_and_global_ai_switch_blocks_start():void{
  $user=$this->user();$paper=$this->paper($user,['status'=>'review_required','percentage'=>70,'grade_awarded'=>GradingService::grade(70)]);$row=$paper->results()->create($this->row());$this->actingAs($user)->patch(route('results.update',$row),['teacher_marks'=>7])->assertRedirect();$this->post(route('results.finalise',$paper))->assertRedirect();$this->assertSame('7',$paper->fresh()->grade_awarded);
  SystemSetting::create(['key'=>'ai_enabled','value'=>'0']);$waiting=$this->paper($user);$this->postJson(route('assessments.start',$waiting))->assertUnprocessable();$this->assertSame('uploaded',$waiting->fresh()->status);Http::assertNothingSent();
 }
 public function test_deleted_papers_do_not_refund_quota_and_replacement_uses_same_reservation():void{
  $user=$this->user();$plan=$user->plan;$limits=$plan->limits;$limits['paper_check_limit']=1;$plan->update(['limits'=>$limits]);$this->actingAs($user)->post(route('assessments.store'),$this->files())->assertRedirect();$paper=Assessment::where('user_id',$user->id)->firstOrFail();
  $this->put(route('assessments.documents',$paper),['written_answer'=>$this->files()['written_answer']])->assertRedirect();$this->assertSame(1,app(QuotaService::class)->paperUsage($user->fresh()));$this->delete(route('assessments.destroy',$paper))->assertRedirect();$this->post(route('assessments.store'),$this->files())->assertUnprocessable();
 }
 public function test_bulk_prevalidation_and_student_cap_leave_no_partial_records_or_files():void{
  $user=$this->user();$foreign=Student::firstOrFail();$files=$this->files();$files['written_answers']=[$files['written_answer'],$this->files()['written_answer']];unset($files['written_answer']);$data=$files+['student_ids'=>[null,$foreign->id],'student_names'=>['New one','Foreign']];$this->actingAs($user)->post(route('assessments.bulk.store'),$data)->assertForbidden();$this->assertSame(0,Assessment::where('user_id',$user->id)->count());$this->assertSame(0,$user->students()->count());$this->assertSame([],Storage::allFiles());
  $limits=$user->plan->limits;$limits['student_limit']=0;$user->plan->update(['limits'=>$limits]);$files=$this->files();$this->post(route('assessments.bulk.store'),['title'=>'Cap','subject'=>'Physics','total_marks'=>10,'question_paper'=>$files['question_paper'],'written_answers'=>[$files['written_answer']],'student_names'=>['Over cap']])->assertUnprocessable();$this->assertSame(0,$user->students()->count());
 }
 public function test_csv_handles_bom_quoted_multiline_and_rejects_uneven_rows_atomically():void{
  $user=$this->user();$this->actingAs($user)->post(route('students.import'),['student_file'=>UploadedFile::fake()->createWithContent('bad.csv',"name,grade\nA,10,extra\n")])->assertUnprocessable();$this->assertSame(0,$user->students()->count());
  $this->post(route('students.import'),['student_file'=>UploadedFile::fake()->createWithContent('good.csv',"\xEF\xBB\xBFname,grade\n\"Aarav\nSharma\",10\n")])->assertRedirect();$this->assertSame("Aarav\nSharma",$user->students()->firstOrFail()->name);
 }
 public function test_inactive_login_and_inactive_owner_existing_session_are_blocked():void{
  $user=$this->user();$user->update(['account_status'=>'inactive']);$this->post('/login',['email'=>$user->email,'password'=>'password'])->assertSessionHasErrors('email');$this->assertGuest();
  $owner=$this->user('mode3');$owner->update(['account_status'=>'suspended']);$this->actingAs($this->user('mode3.teacher'))->get(route('dashboard'))->assertRedirect(route('login'));$this->assertGuest();
 }
 public function test_verification_notice_and_authentication_throttle_work():void{
  $user=$this->user();$user->forceFill(['email_verified_at'=>null])->save();$this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('verification.notice'));$this->get(route('verification.notice'))->assertOk()->assertSee('Verify your email');
  $this->post(route('logout'));for($i=0;$i<7;$i++)$response=$this->post('/login',['email'=>'missing@example.com','password'=>'bad']);$response->assertStatus(429);
 }
 public function test_teacher_without_optional_mobile_and_active_only_allocation_work():void{
  $owner=$this->user('mode3');$this->actingAs($owner)->post(route('team.store'),['name'=>'New teacher','email'=>'newteacher@example.com','password'=>'test-password'])->assertRedirect();$teacher=User::where('email','newteacher@example.com')->firstOrFail();$this->assertNull($teacher->mobile);
  $paper=$this->paper($owner);$this->put(route('assessments.assign',$paper),['assigned_teacher_id'=>$teacher->id])->assertRedirect();$this->assertSame($teacher->id,$paper->fresh()->assigned_teacher_id);$teacher->update(['account_status'=>'inactive']);$this->put(route('assessments.assign',$paper),['assigned_teacher_id'=>$teacher->id])->assertUnprocessable();
 }
 public function test_student_invitation_requires_the_invited_account_before_linking():void{
  $owner=$this->user('mode3');$student=$owner->students()->create(['name'=>'Invited Student']);$login=User::create(['name'=>'Invited Student','email'=>'invited@example.com','password'=>'test-password','user_type'=>'student','account_status'=>'active']);
  DB::table('student_account_invitations')->insert(['student_id'=>$student->id,'email'=>$login->email,'token_hash'=>hash('sha256','known-token'),'expires_at'=>now()->addDay(),'created_at'=>now(),'updated_at'=>now()]);
  $this->actingAs($this->user('student'))->post(route('students.invitation.accept','known-token'))->assertForbidden();$this->assertNull($student->fresh()->login_user_id);$this->actingAs($login)->post(route('students.invitation.accept','known-token'))->assertRedirect(route('portal.student'));$this->assertSame($login->id,$student->fresh()->login_user_id);
 }
 public function test_expired_parent_link_rotates_and_delivery_status_and_preference_are_correct():void{
  Mail::fake();$owner=$this->user('mode3');$paper=$this->paper($owner,['student_id'=>$owner->students()->firstOrFail()->id,'status'=>'finalised','finalised_at'=>now(),'public_report_token'=>'expired-token','public_report_expires_at'=>now()->subDay()]);$paper->results()->create($this->row());
  $this->actingAs($owner)->post(route('parent-report.send',$paper))->assertRedirect()->assertSessionHas('success');$this->assertSame('reported',$paper->fresh()->status);$this->assertNotSame('expired-token',$paper->fresh()->public_report_token);$this->assertTrue($paper->fresh()->public_report_expires_at->isFuture());$this->get(route('parent.report.public','expired-token'))->assertNotFound();
  SystemSetting::create(['key'=>'email_enabled','value'=>'0']);$this->post(route('parent-report.send',$paper))->assertUnprocessable();
 }
 public function test_subscription_expiry_and_settings_and_notification_preferences_have_effect():void{
  $user=$this->user();$user->forceFill(['subscription_ends_at'=>now()->subMinute()])->save();$this->assertSame('free',$user->effectivePlan()->slug);app(SubscriptionService::class)->activate($user,$user->plan);$this->assertSame('mode-2',$user->fresh()->effectivePlan()->slug);
  Notification::fake();$user->update(['notification_preferences'=>['processing'=>false]]);app(LifecycleNotifier::class)->send($user,'processing','Done','Ready');Notification::assertNothingSent();$user->update(['notification_preferences'=>['processing'=>true]]);app(LifecycleNotifier::class)->send($user,'processing','Done','Ready');Notification::assertSentTo($user,\App\Notifications\LifecycleActivity::class);
  SystemSetting::create(['key'=>'maintenance_mode','value'=>'1']);$this->get('/')->assertStatus(503);$this->actingAs($this->user('admin'))->get('/')->assertOk();
 }
 public function test_an_account_without_an_assigned_plan_uses_the_free_lifetime_limit():void{
  $user=$this->user();$user->update(['plan_id'=>null]);$user=$user->fresh();UsageRecord::create(['user_id'=>$user->id,'type'=>'assessment_upload','reference_type'=>Assessment::class,'reference_id'=>999,'created_at'=>now()->subYear()]);
  $this->assertSame('free',$user->effectivePlan()->slug);$this->assertSame(1,app(QuotaService::class)->paperUsage($user));$this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);app(QuotaService::class)->assertPapers($user,1);
 }
 public function test_pdf_preserves_teacher_feedback_parts_long_text_and_unicode():void{
  $paper=$this->paper($this->user(),['status'=>'finalised']);$paper->results()->create($this->row(['question_part'=>'a','teacher_comment'=>str_repeat('Detailed feedback, ',70).'বাংলা মূল্যায়ন। END_OF_LONG_FEEDBACK']));
  $pdf=app(PdfReportService::class)->make($paper);$text=(new \Smalot\PdfParser\Parser())->parseContent($pdf)->getText();$this->assertStringContainsString('1(a)',$text);$this->assertStringContainsString('END_OF_LONG_FEEDBACK',$text);$this->assertStringNotContainsString('Teacher review required.',$text);
  file_put_contents(storage_path('framework/testing/report-unicode-qa.pdf'),$pdf);
 }
}
