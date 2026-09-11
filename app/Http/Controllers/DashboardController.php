<?php
namespace App\Http\Controllers;
class DashboardController extends Controller {
 public function __invoke(){
   $user=request()->user();
   if($user->user_type==='student') return redirect()->route('portal.student');
   if($user->user_type==='teacher' && !$user->is_admin) return redirect()->route('portal.teacher');
   $query=$user->assessments();
   $completed=(clone $query)->whereIn('status',['review_required','reviewed','finalised','reported'])->count();
   $processing=(clone $query)->whereIn('status',['queued','processing'])->count();
   $average=round((float)((clone $query)->whereNotNull('percentage')->avg('percentage') ?? 0),1);
   return view('dashboard',[
     'assessments'=>$query->latest()->with(['student','assignedTeacher'])->take(8)->get(),
     'students'=>$user->students()->count(), 'total'=>$query->count(), 'completed'=>$completed,
     'processing'=>$processing, 'average'=>$average,
   ]);
 }
}