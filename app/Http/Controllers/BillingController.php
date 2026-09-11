<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Plan,User,Assessment,UsageRecord};
class BillingController extends Controller {
 public function index(Request $request){
  $owner=User::findOrFail($request->user()->organisationOwnerId());
  $used=Assessment::where('user_id',$owner->id)->where('created_at','>=',now()->startOfMonth())->count();
  $usage=['paper_checks'=>$used,'rechecks'=>UsageRecord::where('user_id',$owner->id)->where('type','question_recheck')->where('created_at','>=',now()->startOfMonth())->sum('quantity')];
  return view('billing.index',['user'=>$owner->load('plan'),'plans'=>Plan::where('active',true)->orderBy('price')->get(),'used'=>$used,'usage'=>$usage,'canChange'=>!$request->user()->owner_user_id]);
 }
 public function change(Request $request){
  abort_if($request->user()->owner_user_id,403,'Only the coaching-class administrator can change the organisation plan.');
  $data=$request->validate(['plan_id'=>'required|exists:plans,id']);$request->user()->update(['plan_id'=>$data['plan_id']]); if($request->user()->isCoaching())$request->user()->staffAccounts()->update(['plan_id'=>$data['plan_id']]);
  return back()->with('success','Demo subscription changed. In production, call this only after payment-gateway confirmation.');
 }
}
