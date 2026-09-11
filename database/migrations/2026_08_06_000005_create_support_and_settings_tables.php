<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('users', function (Blueprint $table) {
   $table->string('organisation_name')->nullable()->after('user_type');
   $table->string('avatar_path')->nullable()->after('organisation_name');
   $table->json('notification_preferences')->nullable()->after('avatar_path');
  });
  Schema::create('support_tickets', function (Blueprint $table) {
   $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
   $table->string('subject'); $table->string('category')->default('general');
   $table->string('priority')->default('normal'); $table->string('status')->default('open');
   $table->text('message'); $table->text('admin_reply')->nullable(); $table->timestamps();
  });
  Schema::create('team_members', function (Blueprint $table) {
   $table->id(); $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
   $table->string('name'); $table->string('email'); $table->string('mobile')->nullable();
   $table->string('role')->default('teacher'); $table->boolean('active')->default(true); $table->timestamps();
   $table->unique(['owner_id','email']);
  });
 }
 public function down(): void {
  Schema::dropIfExists('team_members'); Schema::dropIfExists('support_tickets');
  Schema::table('users', fn(Blueprint $t)=>$t->dropColumn(['organisation_name','avatar_path','notification_preferences']));
 }
};
