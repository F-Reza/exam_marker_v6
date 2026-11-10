<?php
namespace App\Http\Controllers;
class DashboardController extends Controller {
 public function __invoke(){
   $user=request()->user();
   if(!$user->isPlatformAdmin() && $user->isStudent()) return redirect()->route('portal.student');
   if(!$user->isPlatformAdmin() && $user->isTeacher()) return redirect()->route('portal.teacher');
   $query=$user->accessibleAssessments();
   $completed=(clone $query)->whereIn('status',['review_required','reviewed','finalised','reported'])->count();
   $processing=(clone $query)->whereIn('status',['queued','processing'])->count();
   $average=round((float)((clone $query)->whereNotNull('percentage')->avg('percentage') ?? 0),1);
   return view('dashboard',[
     'assessments'=>(clone $query)->latest()->with(['student','assignedTeacher'])->take(8)->get(),
     'students'=>$user->canAccessModule('students')?$user->organisationStudents()->count():0, 'total'=>$query->count(), 'completed'=>$completed,
     'processing'=>$processing, 'average'=>$average,
   ]);
 }
}
