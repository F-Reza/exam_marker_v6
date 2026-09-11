<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::table('users',function(Blueprint $t){
   $t->foreignId('owner_user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
   $t->string('account_status')->default('active');
   $t->string('role_title')->nullable();
  });
  Schema::table('students',function(Blueprint $t){
   $t->foreignId('login_user_id')->nullable()->constrained('users')->nullOnDelete();
  });
  Schema::table('assessments',function(Blueprint $t){
   $t->foreignId('assigned_teacher_id')->nullable()->constrained('users')->nullOnDelete();
  });
 }
 public function down():void{
  Schema::table('assessments',fn(Blueprint $t)=>$t->dropConstrainedForeignId('assigned_teacher_id'));
  Schema::table('students',fn(Blueprint $t)=>$t->dropConstrainedForeignId('login_user_id'));
  Schema::table('users',function(Blueprint $t){$t->dropConstrainedForeignId('owner_user_id');$t->dropColumn(['account_status','role_title']);});
 }
};
