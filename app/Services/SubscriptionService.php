<?php
namespace App\Services;
use App\Models\{Plan,User};
use Illuminate\Support\Carbon;
class SubscriptionService {
 public function activate(User $owner,Plan $plan):void{
  $renew=$owner->plan_id===$plan->id && $owner->subscription_ends_at?->isFuture();
  $start=$renew?$owner->subscription_started_at:now();
  $months=$renew?max(1,($owner->subscription_ends_at->year-$start->year)*12+$owner->subscription_ends_at->month-$start->month):0;
  $end=$plan->price>0?$start->copy()->addMonthsNoOverflow($months+1):null;
  $owner->forceFill(['plan_id'=>$plan->id,'subscription_started_at'=>$plan->price>0?$start:null,'subscription_ends_at'=>$end,'subscription_cancelled'=>false])->save();
  $owner->staffAccounts()->update(['plan_id'=>$plan->id]);
 }
 public function cycleStart(User $owner):Carbon{
  if(!$owner->subscription_started_at)return now()->startOfMonth();
  $origin=$owner->subscription_started_at->copy();$months=max(0,(now()->year-$origin->year)*12+now()->month-$origin->month);
  $start=$origin->copy()->addMonthsNoOverflow($months);if($start->gt(now()))$start=$origin->copy()->addMonthsNoOverflow(max(0,$months-1));return $start;
 }
}
