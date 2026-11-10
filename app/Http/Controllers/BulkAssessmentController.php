<?php
namespace App\Http\Controllers;
use App\Models\{Assessment,Student,User,UsageRecord};
use App\Jobs\ProcessAssessment;
use Illuminate\Http\Request;
use App\Rules\ExamDocument;
use App\Services\ExamDocumentStorage;
use Illuminate\Support\Facades\Storage;

class BulkAssessmentController extends Controller {
 public function create(Request $request){
  abort_unless($request->user()->canAccessModule('bulk'),403); $owner=$request->user()->organisationOwnerId();
  return view('assessments.bulk',['students'=>Student::where('user_id',$owner)->orderBy('name')->get()]);
 }
 public function store(Request $request){
  $u=$request->user(); abort_unless($u->canAccessModule('bulk'),403); $owner=$u->organisationOwnerId();
  $data=$request->validate([
   'title'=>'required|string|max:150','subject'=>'required|string|max:100','grade'=>'nullable|string|max:50','total_marks'=>'required|numeric|min:1|max:1000',
   'question_paper'=>['bail','required','file','max:51200',new ExamDocument()],'mark_scheme'=>['bail','nullable','file','max:51200',new ExamDocument()],'inserts'=>'nullable|array|max:5','inserts.*'=>['bail','file','max:51200',new ExamDocument()],
   'student_ids'=>'nullable|array','student_ids.*'=>'nullable|integer','student_names'=>'required|array|min:1','student_names.*'=>'nullable|string|max:120','written_answers'=>'required|array|min:1','written_answers.*'=>['bail','required','file','max:51200',new ExamDocument()],'start_now'=>'nullable|boolean','assigned_teacher_id'=>'nullable|integer'
  ]);
  abort_if(count($data['student_names'])!==count($request->file('written_answers')),422,'Each WA must match one student row.');
  abort_if($request->filled('assigned_teacher_id') && !$u->canAccessModule('teacher-assignment'),403);
  $created=app(\App\Services\AssessmentUploadService::class)->create($u,$data,$request->allFiles(),true);
  $failed=0;
  if($request->boolean('start_now')) foreach($created as $a){
   if(!app(\App\Services\AssessmentProcessingService::class)->start($a)) $failed++;
  }
  if($failed) return redirect()->route('assessments.index')->with('error',$failed.' paper(s) could not be checked. Open each failed paper to see the document error and retry. Other uploaded papers were kept.');
  return redirect()->route('assessments.index')->with('success',count($created).' student answer papers uploaded'.($request->boolean('start_now')?' and queued for checking.':'.'));
 }
}
