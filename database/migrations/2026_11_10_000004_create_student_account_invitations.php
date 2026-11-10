<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{Schema::create('student_account_invitations',function(Blueprint $table){$table->id();$table->foreignId('student_id')->constrained()->cascadeOnDelete();$table->string('email');$table->string('token_hash',64)->unique();$table->timestamp('expires_at');$table->timestamp('accepted_at')->nullable();$table->timestamps();});}
 public function down():void{Schema::dropIfExists('student_account_invitations');}
};
