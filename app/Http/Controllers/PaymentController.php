<?php
namespace App\Http\Controllers;
use App\Models\{Plan,PaymentGatewaySetting,PaymentTransaction};
use App\Services\{LifecycleNotifier,SubscriptionService};
use App\Services\Payments\GatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class PaymentController extends Controller {
 public function checkout(Request $request,Plan $plan){
  abort_if($request->user()->owner_user_id,403);abort_unless($plan->active&&$plan->price>0,404);
  $gateways=collect(app(GatewayManager::class)->enabled());return view('billing.checkout',compact('plan','gateways'));
 }
 public function pay(Request $request,Plan $plan,GatewayManager $manager){
  abort_if($request->user()->owner_user_id,403);abort_unless($plan->active&&$plan->price>0,422);
  $data=$request->validate(['gateway'=>'required|in:'.implode(',',GatewayManager::SUPPORTED)]);
  $gateway=PaymentGatewaySetting::where('provider',$data['gateway'])->where('enabled',true)->firstOrFail();
  abort_if($gateway->provider==='paypal'&&config('exam-marker.currency','INR')==='INR',422,'PayPal does not accept INR checkout. Use the configured INR gateway.');
  $transaction=PaymentTransaction::create(['user_id'=>$request->user()->id,'plan_id'=>$plan->id,'gateway'=>$gateway->provider,'reference'=>'EM'.Str::upper(Str::random(24)),'amount'=>$plan->price,'currency'=>config('exam-marker.currency','INR'),'status'=>'pending']);
  try{
   $start=$manager->begin($transaction->load('user','plan'),$gateway);
   $transaction->update(['provider_reference'=>$start['provider_reference'],'provider_payload'=>$start['payload']??null]);
   if(($start['type']??null)==='form')return view('billing.gateway-form',['start'=>$start,'tx'=>$transaction,'gateway'=>$gateway]);
   return redirect()->away($start['url']);
  }catch(\Throwable $error){report($error);$transaction->update(['status'=>'failed']);return redirect()->route('billing.index')->with('error','Checkout could not start. Check payment gateway configuration or contact support.');}
 }
 public function callback(Request $request,string $provider,string $reference,GatewayManager $manager){
  $transaction=PaymentTransaction::where('reference',$reference)->firstOrFail();
  abort_unless($transaction->gateway===$provider&&in_array($provider,GatewayManager::SUPPORTED,true),422,'Payment gateway does not match this transaction.');
  $gateway=PaymentGatewaySetting::where('provider',$provider)->firstOrFail();
  try{
   $paid=DB::transaction(function()use($transaction,$provider,$gateway,$request,$manager){
    $tx=PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
    if($tx->status==='paid')return true;
    if($tx->status!=='pending')return false;
    $result=$manager->verify($provider,$request->all(),$gateway,$tx);
    if(!$result['paid'])return false;
    $tx->update(['status'=>'paid','provider_payload'=>$result['payload'],'paid_at'=>now()]);
    $owner=\App\Models\User::whereKey($tx->user_id)->lockForUpdate()->firstOrFail();
    app(SubscriptionService::class)->activate($owner,$tx->plan);
    app(LifecycleNotifier::class)->send($owner,'billing','Payment confirmed','Your '.$tx->plan->name.' subscription is active.',route('billing.index'));
    return true;
   });
   return redirect()->route('billing.index')->with($paid?'success':'error',$paid?'Payment confirmed. Your plan is active.':'Payment has not been confirmed. Your plan has not changed.');
  }catch(\Throwable $error){report($error);return redirect()->route('billing.index')->with('error','Payment verification failed. Your plan has not changed. Contact support with transaction '.$transaction->reference.'.');}
 }
 public function cancel(Request $request,string $reference){
  $tx=PaymentTransaction::where('reference',$reference)->where('user_id',$request->user()->id)->firstOrFail();
  PaymentTransaction::whereKey($tx->id)->where('status','pending')->update(['status'=>'cancelled']);
  return redirect()->route('billing.index')->with('success','Checkout closed. Confirmed payments remain active.');
 }
 public function cancelSubscription(Request $request){
  abort_if($request->user()->owner_user_id,403);$request->user()->forceFill(['subscription_cancelled'=>true])->save();
  return back()->with('success','Renewal cancelled. Access continues until the displayed expiry date.');
 }
}
