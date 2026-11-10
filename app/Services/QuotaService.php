<?php
namespace App\Services;
use App\Models\{Assessment,UsageRecord,User};
class QuotaService {
 public function paperUsage(User $owner):int{
  $start=$owner->effectivePlan()?->slug==='free'?null:app(SubscriptionService::class)->cycleStart($owner);
  $uploads=UsageRecord::where('user_id',$owner->id)->whereIn('type',['assessment_upload','bulk_assessment_upload']);
  $recorded=(clone $uploads)->pluck('reference_id')->all();
  $query=clone $uploads;if($start)$query->where('created_at','>=',$start);
  $count=(int)$query->sum('quantity');
  $processed=UsageRecord::where('user_id',$owner->id)->where('type','paper_processed')->whereNotIn('reference_id',$recorded);
  if($start)$processed->where('created_at','>=',$start);
  $processedIds=$processed->distinct()->pluck('reference_id')->all();$count+=count($processedIds);
  $legacy=Assessment::where('user_id',$owner->id)->whereNotIn('id',array_merge($recorded,$processedIds));
  if($start)$legacy->where('created_at','>=',$start);
  $count+=$legacy->count();
  return max($count,$owner->effectivePlan()?->slug==='free'&&$owner->free_check_used?1:0);
 }
 public function assertPapers(User $owner,int $incoming):void{
  if($owner->isPlatformAdmin())return;
  abort_if($this->paperUsage($owner)+$incoming>$owner->limit('paper_check_limit',1),422,'Paper-check limit reached. Existing uploaded papers can be retried without another check.');
 }
 public function assertStudents(User $owner,int $incoming):void{
  if($incoming<=0)return;
  if(!$owner->isPlatformAdmin())abort_if($owner->organisationStudents()->count()+$incoming>$owner->limit('student_limit',1),422,'Student limit reached for this plan.');
 }
}
