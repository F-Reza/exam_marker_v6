<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::table('assessments',function(Blueprint $t){
   $t->timestamp('processing_started_at')->nullable();
   $t->timestamp('processing_completed_at')->nullable();
   $t->text('error_message')->nullable();
   $t->unsignedInteger('report_version')->default(1);
  });
  Schema::table('question_results',function(Blueprint $t){
   $t->unsignedInteger('recheck_count')->default(0);
   $t->timestamp('last_rechecked_at')->nullable();
  });
  Schema::create('usage_records',function(Blueprint $t){
   $t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('type');$t->decimal('quantity',12,2)->default(1);$t->nullableMorphs('reference');$t->json('meta')->nullable();$t->timestamps();$t->index(['user_id','type','created_at']);
  });
  Schema::create('audit_logs',function(Blueprint $t){
   $t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->foreignId('organisation_user_id')->nullable()->constrained('users')->nullOnDelete();$t->string('action');$t->string('subject_type')->nullable();$t->unsignedBigInteger('subject_id')->nullable();$t->json('meta')->nullable();$t->string('ip_address',64)->nullable();$t->timestamps();$t->index(['organisation_user_id','created_at']);
  });
 }
 public function down():void{
  Schema::dropIfExists('audit_logs');Schema::dropIfExists('usage_records');
  Schema::table('question_results',fn(Blueprint $t)=>$t->dropColumn(['recheck_count','last_rechecked_at']));
  Schema::table('assessments',fn(Blueprint $t)=>$t->dropColumn(['processing_started_at','processing_completed_at','error_message','report_version']));
 }
};
