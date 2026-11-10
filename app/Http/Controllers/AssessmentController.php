<?php
namespace App\Http\Controllers;
use App\Models\{Assessment,Student,User,UsageRecord};
use App\Jobs\ProcessAssessment;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Storage;

class AssessmentController extends Controller {
 public function index(){
  $u=request()->user();
  $q=$u->accessibleAssessments();
  return view('assessments.index',['assessments'=>$q->latest()->with(['student','assignedTeacher'])->paginate(12)]);
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
   'question_paper'=>['required',File::types(['pdf','doc','docx'])->max(50*1024)],
   'mark_scheme'=>['nullable',File::types(['pdf','doc','docx'])->max(50*1024)],
   'written_answer'=>['required',File::types(['pdf','doc','docx'])->max(50*1024)],
   'inserts'=>'nullable|array|max:5','inserts.*'=>['file',File::types(['pdf','doc','docx'])->max(50*1024)],
  ]);
  if($user->isStudent()){ $data['student_id']=null; $data['assigned_teacher_id']=null; }
  if(!empty($data['student_id']) && !Student::where('user_id',$ownerId)->whereKey($data['student_id'])->exists()) abort(403);
  if(!empty($data['assigned_teacher_id'])){
   abort_unless($user->canAccessModule('teacher-assignment'),403,'Teacher assignment is available in Mode 3.');
   abort_unless(User::where('owner_user_id',$ownerId)->where('user_type','teacher')->whereKey($data['assigned_teacher_id'])->exists(),403);
  }
  if($user->isTeacher() && $user->owner_user_id) $data['assigned_teacher_id']=$user->id;
  $assessment=Assessment::create([
   'user_id'=>$ownerId,'student_id'=>$data['student_id']??null,'assigned_teacher_id'=>$data['assigned_teacher_id']??null,'title'=>$data['title'],'subject'=>$data['subject'],'grade'=>$data['grade']??null,'exam_board'=>$data['exam_board']??null,'assessment_date'=>$data['assessment_date']??null,'total_marks'=>$data['total_marks'],'status'=>'uploaded',
   'question_paper_path'=>$request->file('question_paper')->store('private/papers/qp'),
   'mark_scheme_path'=>$request->file('mark_scheme')?->store('private/papers/ms'),
   'written_answer_path'=>$request->file('written_answer')->store('private/papers/wa'),
  ]);
  foreach($request->file('inserts',[]) as $insert){$assessment->attachments()->create(['type'=>'insert','path'=>$insert->store('private/papers/inserts'),'original_name'=>$insert->getClientOriginalName()]);}
  UsageRecord::create(['user_id'=>$ownerId,'type'=>'assessment_upload','reference_type'=>Assessment::class,'reference_id'=>$assessment->id]);
  $audit->log($assessment,'assessment.created',['has_ms'=>(bool)$assessment->mark_scheme_path]);
  return redirect()->route('assessments.show',$assessment)->with('success','QP, optional MS, inserts and WA uploaded successfully. Review and start checking.');
 }
 public function show(Assessment $assessment){ $this->authorizeAssessment($assessment); return view('assessments.show',compact('assessment')); }
 public function start(Assessment $assessment, AuditService $audit){
  $this->authorizeAssessment($assessment);
  abort_if(request()->user()->isStudent() && $assessment->user_id!==request()->user()->id,403,'Students can only start checking their own uploaded papers.');
  abort_unless(in_array($assessment->status,['uploaded','failed']),422);
  $assessment->update(['status'=>'queued','error_message'=>null]); $audit->log($assessment,'assessment.queued');
  if(config('exam-marker.async',false)) ProcessAssessment::dispatch($assessment->id); else ProcessAssessment::dispatchSync($assessment->id);
  $owner=User::find($assessment->user_id); if($owner?->plan?->slug==='free') $owner->update(['free_check_used'=>true]);
  return redirect()->route('assessments.processing',$assessment);
 }
 public function destroy(Assessment $assessment, AuditService $audit){
  $this->authorizeAssessment($assessment,true); $audit->log($assessment,'assessment.deleted');
  foreach(['question_paper_path','mark_scheme_path','written_answer_path'] as $field) if($assessment->$field) Storage::delete($assessment->$field); foreach($assessment->attachments as $att) if($att->path) Storage::delete($att->path);
  $assessment->delete(); return redirect()->route('assessments.index')->with('success','Assessment deleted.');
 }
 private function enforceCheckLimit(User $user,int $incoming): void {
  if($user->isPlatformAdmin()) return;
  $owner=User::findOrFail($user->organisationOwnerId());
  $isFree=$owner->effectivePlan()?->slug==='free';
  $usedQuery=Assessment::where('user_id',$owner->id);
  if(!$isFree) $usedQuery->where('created_at','>=',now()->startOfMonth());
  $limit=$owner->limit('paper_check_limit',1); $used=$usedQuery->count();
  abort_if($isFree && ($owner->free_check_used || $used+$incoming>$limit),422,'Your free paper check has been used. Select a plan to continue.');
  abort_if(!$isFree && $used+$incoming>$limit,422,'Your paper-check limit would be exceeded.');
 }
 private function authorizeAssessment(Assessment $assessment,bool $ownerOnly=false): void {
  $u=request()->user(); if($u->isPlatformAdmin()) return;
  if($ownerOnly) abort_unless($u->canDeleteAssessment($assessment),403);
  else abort_unless($u->canAccessAssessment($assessment),403);
 }
}
