<?php
namespace App\Services;
use App\Models\{Assessment,Student,UsageRecord,User};
use Illuminate\Support\Facades\{DB,Storage};
class AssessmentUploadService {
 public function create(User $actor,array $data,array $files,bool $bulk=false):array{
  $ownerId=$actor->organisationOwnerId();$answers=$bulk?$files['written_answers']:[$files['written_answer']];
  $teacher=$actor->isTeacher()&&$actor->owner_user_id?$actor->id:($data['assigned_teacher_id']??null);
  if($teacher)abort_unless(User::whereKey($teacher)->where('owner_user_id',$ownerId)->where('user_type','teacher')->where('account_status','active')->exists(),403,'Select an active teacher from your organisation.');
  $rows=[];$names=[];
  foreach($answers as $index=>$answer){
   $id=$bulk?($data['student_ids'][$index]??null):($data['student_id']??null);
   $name=$bulk?trim((string)($data['student_names'][$index]??'')):null;
   if($id)abort_unless(Student::whereKey($id)->where('user_id',$ownerId)->exists(),403);
   elseif($bulk){abort_if($name==='',422,'Enter or select a student for every answer script.');$names[]=$name;}
   $rows[]=[$id,$name,$answer];
  }
  $paths=[];
  try{return DB::transaction(function()use($actor,$data,$files,$bulk,$ownerId,$teacher,$rows,$names,&$paths){
   $owner=User::whereKey($ownerId)->lockForUpdate()->firstOrFail();$quota=app(QuotaService::class);$quota->assertPapers($owner,count($rows));
   $existing=Student::where('user_id',$ownerId)->pluck('name')->map(fn($name)=>mb_strtolower(trim($name)))->all();
   $normalized=array_unique(array_map(fn($name)=>mb_strtolower($name),$names));
   $quota->assertStudents($owner,count(array_diff($normalized,$existing)));
   $put=function($file,$folder)use(&$paths){$path=ExamDocumentStorage::put($file,$folder);if($path)$paths[]=$path;return $path;};
   $created=[];
   foreach($rows as [$studentId,$name,$answer]){
    if(!$studentId&&$bulk)$studentId=(Student::where('user_id',$ownerId)->whereRaw('LOWER(name)=?',[mb_strtolower($name)])->first()??Student::create(['user_id'=>$ownerId,'name'=>$name,'grade'=>$data['grade']??null]))->id;
    $paper=Assessment::create([
     'user_id'=>$ownerId,'student_id'=>$studentId,'assigned_teacher_id'=>$teacher,'title'=>$data['title'],'subject'=>$data['subject'],'grade'=>$data['grade']??null,
     'exam_board'=>$data['exam_board']??null,'assessment_date'=>$data['assessment_date']??null,'total_marks'=>$data['total_marks'],'status'=>'uploaded',
     'question_paper_path'=>$put($files['question_paper'],'private/papers/qp'),'mark_scheme_path'=>$put($files['mark_scheme']??null,'private/papers/ms'),'written_answer_path'=>$put($answer,'private/papers/wa'),
    ]);
    foreach($files['inserts']??[] as $insert)$paper->attachments()->create(['type'=>'insert','path'=>$put($insert,'private/papers/inserts'),'original_name'=>$insert->getClientOriginalName()]);
    UsageRecord::create(['user_id'=>$ownerId,'type'=>$bulk?'bulk_assessment_upload':'assessment_upload','reference_type'=>Assessment::class,'reference_id'=>$paper->id]);
    app(AuditService::class)->log($paper,'assessment.created',['has_ms'=>(bool)$paper->mark_scheme_path]);$created[]=$paper;
   }
   if($owner->effectivePlan()?->slug==='free')$owner->update(['free_check_used'=>true]);
   return $created;
  });}catch(\Throwable $error){if($paths)Storage::delete($paths);throw $error;}
 }
}
