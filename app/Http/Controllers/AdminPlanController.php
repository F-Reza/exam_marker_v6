<?php
namespace App\Http\Controllers;
use App\Models\{Plan,User};
use Illuminate\Http\Request;
class AdminPlanController extends Controller {
 public function index(){return view('admin.plans',['plans'=>Plan::all(),'users'=>User::orderBy('name')->get()]);}
 public function update(Request $r,Plan $plan){
  $d=$r->validate(['price'=>'required|numeric|min:0','paper_check_limit'=>'required|integer|min:1','student_limit'=>'required|integer|min:0','teacher_limit'=>'nullable|integer|min:0','email_limit'=>'nullable|integer|min:0','sms_limit'=>'nullable|integer|min:0','recheck_limit'=>'nullable|integer|min:0']);
  $features=$plan->features; foreach(array_keys($features) as $k)$features[$k]=$r->boolean('features.'.$k);
  $limits=array_merge($plan->limits,['paper_check_limit'=>(int)$d['paper_check_limit'],'student_limit'=>(int)$d['student_limit'],'teacher_limit'=>(int)($d['teacher_limit']??($plan->limits['teacher_limit']??1)),'email_limit'=>(int)($d['email_limit']??($plan->limits['email_limit']??0)),'sms_limit'=>(int)($d['sms_limit']??($plan->limits['sms_limit']??0)),'recheck_limit'=>(int)($d['recheck_limit']??($plan->limits['recheck_limit']??0))]);
  $plan->update(['price'=>$d['price'],'features'=>$features,'limits'=>$limits]);return back()->with('success','Plan features and limits updated.');
 }
 public function assign(Request $r){$d=$r->validate(['user_id'=>'required|exists:users,id','plan_id'=>'required|exists:plans,id']);$u=User::findOrFail($d['user_id']);$u->update(['plan_id'=>$d['plan_id']]);if($u->isCoaching())$u->staffAccounts()->update(['plan_id'=>$d['plan_id']]);return back()->with('success','Plan assigned. Linked coaching teachers were updated too.');}
}
