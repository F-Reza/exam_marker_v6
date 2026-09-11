<?php
namespace App\Http\Controllers;
use App\Models\{Assessment,Student};
class PortalController extends Controller {
 public function student(){
  $u=auth()->user(); $student=Student::where('login_user_id',$u->id)->first();
  $own=Assessment::where('user_id',$u->id); $linked=$student?Assessment::where('student_id',$student->id):Assessment::whereRaw('1=0');
  $ids=$own->pluck('id')->merge($linked->pluck('id'))->unique(); $assessments=Assessment::whereIn('id',$ids)->latest()->paginate(10);
  return view('portal.student',compact('student','assessments'));
 }
 public function teacher(){
  $u=auth()->user();$assessments=Assessment::where('assigned_teacher_id',$u->id)->orWhere('user_id',$u->id)->latest()->paginate(12);return view('portal.teacher',compact('assessments'));
 }
}
