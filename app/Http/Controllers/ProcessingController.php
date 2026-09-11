<?php
namespace App\Http\Controllers;
use App\Models\Assessment;
class ProcessingController extends Controller {
 public function show(Assessment $assessment){abort_unless(request()->user()->canAccessAssessment($assessment),403);return view('assessments.processing',compact('assessment'));}
 public function status(Assessment $assessment){abort_unless(request()->user()->canAccessAssessment($assessment),403);$a=$assessment->fresh();return response()->json(['status'=>$a->status,'error'=>$a->error_message,'url'=>route('results.show',$a)]);}
}
