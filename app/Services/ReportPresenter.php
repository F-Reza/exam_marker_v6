<?php
namespace App\Services;
use App\Models\{Assessment,User};
class ReportPresenter {
 public function data(Assessment $assessment,?User $viewer=null):array{
  $assessment->loadMissing('results','student','user');$viewer??=$assessment->user;
  $rows=$assessment->results->map(function($result){
   try{$label=QuestionStructure::canonical((string)$result->question_number,$result->question_part);}catch(\RuntimeException){$label=(string)$result->question_number;}
   return ['label'=>$label,'maximum'=>(float)$result->max_marks,'marks'=>$result->finalMarks(),'feedback'=>$result->teacher_comment?:$result->feedback,'rubric'=>$result->writing_rubric??[]];
  })->sort(fn($a,$b)=>strnatcasecmp($a['label'],$b['label']))->values()->all();
  $maximum=array_sum(array_column($rows,'maximum'));$obtained=array_sum(array_column($rows,'marks'));
  $percentage=$maximum?round($obtained/$maximum*100,2):0;
  return ['assessment'=>$assessment,'rows'=>$rows,'maximum'=>$maximum,'obtained'=>$obtained,'percentage'=>$percentage,'grade'=>GradingService::grade($percentage,$assessment),'showPercentage'=>$viewer?->hasFeature('percentage')??false,'showGrade'=>$viewer?->hasFeature('grade')??false];
 }
}
