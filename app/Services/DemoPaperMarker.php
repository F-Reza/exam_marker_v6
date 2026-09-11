<?php
namespace App\Services;
use App\Models\Assessment;
class DemoPaperMarker implements PaperMarker {
 public function mark(Assessment $a): array {
  $topics=['Algebra','Calculus','Geometry','Trigonometry','Statistics']; $ratios=[.8,.72,.9,.63,.85];
  $count=count($topics); $base=(float)$a->total_marks/$count; $questions=[]; $obtained=0;
  foreach($topics as $idx=>$topic){
   $max=round($base,1); $score=round($max*$ratios[$idx],1); $obtained+=$score;
   $questions[]=[
    'question_number'=>'Q'.($idx+1),'topic'=>$topic,'max_marks'=>$max,'ai_marks'=>$score,
    'confidence'=>$idx===3?'low':'high','feedback'=>$idx===3?'The method is partly visible, but the written working needs teacher review.':'Relevant method and mostly accurate working were identified.',
    'criteria'=>[['label'=>'Relevant method used','awarded'=>true],['label'=>'Accurate final answer','awarded'=>$ratios[$idx]>.75]],
    'status'=>$idx===3?'review_required':'ai_checked'
   ];
  }
  $pct=round($obtained/max(1,(float)$a->total_marks)*100,2);
  return ['questions'=>$questions,'percentage'=>$pct,'grade'=>$pct>=90?'A+':($pct>=80?'A':($pct>=70?'B':($pct>=60?'C':'Needs Improvement'))),'summary'=>['strengths'=>['Good method selection','Most answers are clearly structured'],'weaknesses'=>['Review unclear working in Q4','Show complete calculation steps'],'notice'=>$a->mark_scheme_path?'Official mark scheme uploaded. Demo adapter used for this local build.':'No official MS was uploaded. A suggested structure was used.']];
 }
}
