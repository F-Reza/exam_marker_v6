<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void{
  Schema::table('users',function(Blueprint $table){
   $table->string('mobile',30)->nullable()->change();
   $table->timestamp('subscription_started_at')->nullable();
   $table->timestamp('subscription_ends_at')->nullable();
   $table->boolean('subscription_cancelled')->default(false);
  });
  // Existing paid owners receive a transition month; future access follows verified payments.
  $paid=DB::table('plans')->where('price','>',0)->pluck('id');
  DB::table('users')->whereIn('plan_id',$paid)->whereNull('owner_user_id')->update(['subscription_started_at'=>now(),'subscription_ends_at'=>now()->addMonthNoOverflow()]);
 }
 public function down():void{Schema::table('users',fn(Blueprint $table)=>$table->dropColumn(['subscription_started_at','subscription_ends_at','subscription_cancelled']));}
};
