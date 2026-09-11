<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('assessments', function (Blueprint $table) {
   $table->string('public_report_token',64)->nullable()->unique()->after('grade_awarded');
   $table->timestamp('public_report_expires_at')->nullable()->after('public_report_token');
   $table->timestamp('finalised_at')->nullable()->after('public_report_expires_at');
  });
  Schema::table('question_results', function (Blueprint $table) {
   $table->string('topic')->nullable()->after('question_number');
   $table->json('criteria')->nullable()->after('feedback');
  });
 }
 public function down(): void {
  Schema::table('question_results', fn(Blueprint $t)=>$t->dropColumn(['topic','criteria']));
  Schema::table('assessments', fn(Blueprint $t)=>$t->dropColumn(['public_report_token','public_report_expires_at','finalised_at']));
 }
};
