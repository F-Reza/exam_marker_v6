<?php
namespace App\Http\Controllers;
use App\Models\{Assessment,Student,User,UsageRecord};
use App\Jobs\ProcessAssessment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use Illuminate\Support\Facades\Storage;

class BulkAssessmentController extends Controller {
 public function create(Request $request){
  abort_unless($request->user()->hasFeature('bulk_answer_upload'),403); $owner=$request->user()->organisationOwnerId();
  return view('assessments.bulk',['students'=>Student::where('user_id',$owner)->orderBy('name')->get()]);
 }
 public function store(Request $request){
  $u=$request->user(); abort_unless($u->hasFeature('bulk_answer_upload'),403); $owner=$u->organisationOwnerId();
  $data=$request->validate([
   'title'=>'required|string|max:150','subject'=>'required|string|max:100','grade'=>'nullable|string|max:50','total_marks'=>'required|numeric|min:1|max:1000',
   'question_paper'=>['required',File::types(['pdf','doc','docx'])->max(50*1024)],'mark_scheme'=>['nullable',File::types(['pdf','doc','docx'])->max(50*1024)],'inserts'=>'nullable|array|max:5','inserts.*'=>['file',File::types(['pdf','doc','docx'])->max(50*1024)],
   'student_ids'=>'nullable|array','student_ids.*'=>'nullable|integer','student_names'=>'required|array|min:1','student_names.*'=>'nullable|string|max:120','written_answers'=>'required|array|min:1','written_answers.*'=>['required',File::types(['pdf','doc','docx'])->max(50*1024)],'start_now'=>'nullable|boolean'
  ]);
  abort_if(count($data['student_names'])!==count($request->file('written_answers')),422,'Each WA must match one student row.');
  $ownerUser=User::findOrFail($owner); $used=Assessment::where('user_id',$owner)->where('created_at','>=',now()->startOfMonth())->count(); $limit=$ownerUser->limit('paper_check_limit',1);
  abort_if($used+count($data['student_names'])>$limit,422,'Bulk upload would exceed your plan paper-check limit.');
  $created=[];
  foreach($data['student_names'] as $i=>$studentName){
   $studentId=$data['student_ids'][$i]??null;
   if($studentId){abort_unless(Student::where('user_id',$owner)->whereKey($studentId)->exists(),403);} else {abort_if(trim((string)$studentName)==='',422,'Enter or select a student name for each answer paper.');$student=Student::firstOrCreate(['user_id'=>$owner,'name'=>trim($studentName)],['grade'=>$data['grade']??null]);$studentId=$student->id;}
   $qp=$request->file('question_paper')->store('private/papers/qp'); $ms=$request->file('mark_scheme')?->store('private/papers/ms'); $wa=$request->file('written_answers')[$i]->store('private/papers/wa');
   $a=Assessment::create(['user_id'=>$owner,'student_id'=>$studentId,'title'=>$data['title'],'subject'=>$data['subject'],'grade'=>$data['grade']??null,'total_marks'=>$data['total_marks'],'status'=>'uploaded','question_paper_path'=>$qp,'mark_scheme_path'=>$ms,'written_answer_path'=>$wa]);
   foreach($request->file('inserts',[]) as $insert){$a->attachments()->create(['type'=>'insert','path'=>$insert->store('private/papers/inserts'),'original_name'=>$insert->getClientOriginalName()]);}
   UsageRecord::create(['user_id'=>$owner,'type'=>'bulk_assessment_upload','reference_type'=>Assessment::class,'reference_id'=>$a->id]); $created[]=$a;
  }
  if($request->boolean('start_now')) foreach($created as $a){$a->update(['status'=>'queued']); config('exam-marker.async',false)?ProcessAssessment::dispatch($a->id):ProcessAssessment::dispatchSync($a->id);}
  return redirect()->route('assessments.index')->with('success',count($created).' student answer papers uploaded'.($request->boolean('start_now')?' and queued for checking.':'.'));
 }
}
