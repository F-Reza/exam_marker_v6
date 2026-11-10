<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Assessment;
class AnalyticsController extends Controller {
 public function index(){
  $user=request()->user(); abort_unless($user->canAccessModule('analytics'),403);
  $assessments=$user->accessibleAssessments()->with('results')->whereNotNull('percentage')->latest()->take(20)->get();
  $topicRows=$user->hasFeature('topic_analysis')
   ? DB::table('question_results')->whereIn('assessment_id',$user->accessibleAssessments()->select('assessments.id'))
    ->selectRaw("COALESCE(topic,'General') as topic, SUM(COALESCE(teacher_marks,ai_marks)) as obtained, SUM(max_marks) as maximum")
    ->groupBy('topic')->get()->map(fn($r)=>['topic'=>$r->topic,'percentage'=>$r->maximum?round($r->obtained/$r->maximum*100,1):0])
   : collect();
  return view('analytics.index',compact('assessments','topicRows'));
 }
}
