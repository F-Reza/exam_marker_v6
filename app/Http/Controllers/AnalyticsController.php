<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Assessment;
class AnalyticsController extends Controller {
 public function index(){
  $user=request()->user(); abort_unless($user->hasFeature('graphs'),403); $owner=$user->organisationOwnerId();
  $assessments=Assessment::where('user_id',$owner)->whereNotNull('percentage')->latest()->take(20)->get();
  $topicRows=DB::table('question_results')->join('assessments','question_results.assessment_id','=','assessments.id')
   ->where('assessments.user_id',$owner)->selectRaw("COALESCE(question_results.topic,'General') as topic, SUM(COALESCE(question_results.teacher_marks,question_results.ai_marks)) as obtained, SUM(question_results.max_marks) as maximum")
   ->groupBy('topic')->get()->map(fn($r)=>['topic'=>$r->topic,'percentage'=>$r->maximum?round($r->obtained/$r->maximum*100,1):0]);
  return view('analytics.index',compact('assessments','topicRows'));
 }
}
