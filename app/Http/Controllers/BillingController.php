<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\{Plan,User,Assessment,UsageRecord};
class BillingController extends Controller {
 public function index(Request $request){
  $owner=User::findOrFail($request->user()->organisationOwnerId());
  $isFree=$owner->effectivePlan()?->slug==='free';
  $checks=Assessment::where('user_id',$owner->id);
  if(!$isFree) $checks->where('created_at','>=',now()->startOfMonth());
  $used=app(\App\Services\QuotaService::class)->paperUsage($owner);
  $usage=['paper_checks'=>$used,'rechecks'=>UsageRecord::where('user_id',$owner->id)->where('type','question_recheck')->where('created_at','>=',now()->startOfMonth())->sum('quantity')];
  return view('billing.index',['user'=>$owner->load('plan'),'plans'=>Plan::where('active',true)->orderBy('price')->get(),'used'=>$used,'usage'=>$usage,'canChange'=>!$request->user()->owner_user_id]);
 }
 public function change(Request $request){
  abort_if($request->user()->owner_user_id,403,'Only the coaching-class administrator can change the organisation plan.');
  $data=$request->validate(['plan_id'=>'required|exists:plans,id']);$plan=Plan::where('active',true)->findOrFail($data['plan_id']);
  if($plan->price>0)return redirect()->route('payments.checkout',$plan)->with('error','Complete payment to activate this plan.');
  app(\App\Services\SubscriptionService::class)->activate($request->user(),$plan);
  app(\App\Services\LifecycleNotifier::class)->send($request->user(),'billing','Plan updated','Your account is now on '.$plan->name,route('billing.index'));
  return back()->with('success','Plan updated.');
 }
}
