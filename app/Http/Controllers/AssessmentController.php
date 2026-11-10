<?php
namespace App\Http\Controllers;
use App\Models\{Assessment,Student,User,UsageRecord};
use App\Jobs\ProcessAssessment;
use App\Services\AuditService;
use Illuminate\Http\Request;
use App\Rules\ExamDocument;
use App\Services\ExamDocumentStorage;
use Illuminate\Support\Facades\Storage;

class AssessmentController extends Controller {
 public function index(){
  $u=request()->user();
  $q=$u->accessibleAssessments();
  return view('assessments.index',['assessments'=>$q->latest()->with(['student','assignedTeacher','results'])->paginate(12)]);
 }
 public function create(){
  $u=request()->user(); $owner=$u->organisationOwnerId();
  if(!$u->isPlatformAdmin()){
   $ownerUser=User::findOrFail($owner);
   if($ownerUser->effectivePlan()?->slug==='free' && ($ownerUser->free_check_used || Assessment::where('user_id',$owner)->count()>=$ownerUser->limit('paper_check_limit',1))){
    return redirect()->route('billing.index')->with('error','Your free paper check has already been used or uploaded. Open My Papers to continue an existing paper, or choose a plan for more checks.');
   }
  }
  return view('assessments.create',['students'=>$u->canAccessModule('students')?Student::where('user_id',$owner)->orderBy('name')->get():collect(),'teachers'=>$u->canAccessModule('teacher-assignment')?User::where('owner_user_id',$owner)->where('user_type','teacher')->where('account_status','active')->orderBy('name')->get():collect()]);
 }
 public function store(Request $request, AuditService $audit){
  $user=$request->user(); $ownerId=$user->organisationOwnerId();
  abort_if($request->filled('student_id') && !$user->canAccessModule('students'),403,'Student records are not available for your account.');
  abort_if($request->filled('assigned_teacher_id') && !$user->canAccessModule('teacher-assignment'),403,'Teacher allocation is only available to a coaching administrator with this feature.');
  $this->enforceCheckLimit($user,1);
  $data=$request->validate([
   'title'=>'required|string|max:150','subject'=>'required|string|max:100','grade'=>'nullable|string|max:50','exam_board'=>'nullable|string|max:100','assessment_date'=>'nullable|date','total_marks'=>'required|numeric|min:1|max:1000','student_id'=>'nullable|exists:students,id','assigned_teacher_id'=>'nullable|exists:users,id',
   'question_paper'=>['bail','required','file','max:51200',new ExamDocument()],
   'mark_scheme'=>['bail','nullable','file','max:51200',new ExamDocument()],
   'written_answer'=>['bail','required','file','max:51200',new ExamDocument()],
   'inserts'=>'nullable|array|max:5','inserts.*'=>['bail','file','max:51200',new ExamDocument()],
  ]);
  if($user->isStudent() && !$user->isPlatformAdmin()){ $data['student_id']=null; $data['assigned_teacher_id']=null; }
  if(!empty($data['student_id']) && !Student::where('user_id',$ownerId)->whereKey($data['student_id'])->exists()) abort(403);
  if(!empty($data['assigned_teacher_id'])){
   abort_unless($user->canAccessModule('teacher-assignment'),403,'Teacher assignment is available in Mode 3.');
   abort_unless(User::where('owner_user_id',$ownerId)->where('user_type','teacher')->where('account_status','active')->whereKey($data['assigned_teacher_id'])->exists(),403);
  }
  if($user->isTeacher() && $user->owner_user_id && !$user->isPlatformAdmin()) $data['assigned_teacher_id']=$user->id;
  $assessment=app(\App\Services\AssessmentUploadService::class)->create($user,$data,$request->allFiles())[0];
  return redirect()->route('assessments.show',$assessment)->with('success','QP, optional MS, inserts and WA uploaded successfully. Review and start checking.');
 }
 public function show(Assessment $assessment){ $this->authorizeAssessment($assessment); return view('assessments.show',compact('assessment')); }
 public function start(Assessment $assessment, AuditService $audit){
  $this->authorizeAssessment($assessment);
  abort_if(request()->user()->isStudent() && !request()->user()->isPlatformAdmin() && $assessment->user_id!==request()->user()->id,403,'Students can only start checking their own uploaded papers.');
  abort_unless(in_array($assessment->status,['uploaded','failed']),422);
  if(!app(\App\Services\AssessmentProcessingService::class)->start($assessment)) {
   return redirect()->route('assessments.show',$assessment)->with('error',$assessment->fresh()->error_message);
  }
  $owner=User::find($assessment->user_id); if($owner?->plan?->slug==='free') $owner->update(['free_check_used'=>true]);
  return redirect()->route('assessments.processing',$assessment);
 }
 public function destroy(Assessment $assessment, AuditService $audit){
  $this->authorizeAssessment($assessment,true); $audit->log($assessment,'assessment.deleted');
  foreach(['question_paper_path','mark_scheme_path','written_answer_path'] as $field) if($assessment->$field) Storage::delete($assessment->$field); foreach($assessment->attachments as $att) if($att->path) Storage::delete($att->path);
  $assessment->delete(); return redirect()->route('assessments.index')->with('success','Assessment deleted.');
 }
 public function assign(Request $request,Assessment $assessment){
  abort_unless($request->user()->canAccessModule('teacher-assignment') && ($request->user()->isPlatformAdmin()||$assessment->user_id===$request->user()->id),403);
  $data=$request->validate(['assigned_teacher_id'=>'nullable|integer']);$teacher=$data['assigned_teacher_id']??null;
  if($teacher)abort_unless(User::whereKey($teacher)->where('owner_user_id',$assessment->user_id)->where('user_type','teacher')->where('account_status','active')->exists(),422,'Choose an active teacher from this organisation.');
  $assessment->update(['assigned_teacher_id'=>$teacher]);
  if($teacher)app(\App\Services\LifecycleNotifier::class)->send(User::find($teacher),'processing','Paper allocated','A paper has been allocated to you.',route('assessments.show',$assessment));
  return back()->with('success','Teacher allocation updated.');
 }
 public function replaceDocuments(Request $request,Assessment $assessment){
  $this->authorizeAssessment($assessment);
  $rules=['question_paper'=>['nullable','file','max:51200',new ExamDocument],'mark_scheme'=>['nullable','file','max:51200',new ExamDocument],'written_answer'=>['nullable','file','max:51200',new ExamDocument],'inserts'=>'nullable|array|max:5','inserts.*'=>['file','max:51200',new ExamDocument],'remove_mark_scheme'=>'nullable|boolean'];
  $request->validate($rules);abort_if(!$request->allFiles()&&!$request->boolean('remove_mark_scheme'),422,'Select a document to replace.');$new=[];$old=[];
  try{\Illuminate\Support\Facades\DB::transaction(function()use($request,$assessment,&$new,&$old){
   $paper=Assessment::whereKey($assessment->id)->lockForUpdate()->firstOrFail();abort_unless(in_array($paper->status,['uploaded','failed'],true),422,'Only an uploaded or failed paper can have its documents replaced.');
   $changes=['status'=>'uploaded','error_message'=>null,'processing_token'=>null,'processing_claim_token'=>null,'processing_started_at'=>null,'processing_completed_at'=>null];
   foreach(['question_paper'=>'question_paper_path','mark_scheme'=>'mark_scheme_path','written_answer'=>'written_answer_path']as$field=>$column){if($request->hasFile($field)){$path=ExamDocumentStorage::put($request->file($field),'private/papers/replacements');$new[]=$path;$old[]=$paper->$column;$changes[$column]=$path;}}
   if($request->boolean('remove_mark_scheme')&&!$request->hasFile('mark_scheme')){$old[]=$paper->mark_scheme_path;$changes['mark_scheme_path']=null;}
   if($request->hasFile('inserts')){foreach($paper->attachments()->where('type','insert')->get()as$attachment){$old[]=$attachment->path;$attachment->delete();}foreach($request->file('inserts')as$file){$path=ExamDocumentStorage::put($file,'private/papers/inserts');$new[]=$path;$paper->attachments()->create(['type'=>'insert','path'=>$path,'original_name'=>$file->getClientOriginalName()]);}}
   $paper->update($changes);
  });}catch(\Throwable $error){if($new)Storage::delete($new);throw$error;}
  Storage::delete(array_filter($old));return back()->with('success','Documents replaced. Check this paper again; no additional paper check was reserved.');
 }
 private function enforceCheckLimit(User $user,int $incoming): void {
  if($user->isPlatformAdmin()) return;
  app(\App\Services\QuotaService::class)->assertPapers(User::findOrFail($user->organisationOwnerId()),$incoming);
 }
 private function authorizeAssessment(Assessment $assessment,bool $ownerOnly=false): void {
  $u=request()->user(); if($u->isPlatformAdmin()) return;
  if($ownerOnly) abort_unless($u->canDeleteAssessment($assessment),403);
  else abort_unless($u->canAccessAssessment($assessment),403);
 }
}
