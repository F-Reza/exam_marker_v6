<?php
namespace App\Http\Controllers;
use App\Models\{Assessment,Student};
class PortalController extends Controller {
 public function student(){
  $u=auth()->user(); $student=Student::where('login_user_id',$u->id)->first();
  $assessments=$u->accessibleAssessments()->with('student')->latest()->paginate(10);
  return view('portal.student',compact('student','assessments'));
 }
 public function teacher(){
  $u=auth()->user();$assessments=$u->accessibleAssessments()->with('student')->latest()->paginate(12);return view('portal.teacher',compact('assessments'));
 }
}
