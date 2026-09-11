<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('assessment_attachments', function(Blueprint $t){
   $t->id(); $t->foreignId('assessment_id')->constrained()->cascadeOnDelete();
   $t->string('type')->default('insert'); $t->string('path'); $t->string('original_name')->nullable(); $t->timestamps();
  });
  Schema::create('payment_gateway_settings', function(Blueprint $t){
   $t->id(); $t->string('provider')->unique(); $t->string('label'); $t->boolean('enabled')->default(false);
   $t->string('environment')->default('sandbox'); $t->text('credentials')->nullable(); $t->json('options')->nullable(); $t->timestamps();
  });
  Schema::create('payment_transactions', function(Blueprint $t){
   $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->foreignId('plan_id')->constrained()->cascadeOnDelete();
   $t->string('gateway'); $t->string('reference')->unique(); $t->string('provider_reference')->nullable();
   $t->decimal('amount',10,2); $t->string('currency',3)->default('INR'); $t->string('status')->default('created');
   $t->json('provider_payload')->nullable(); $t->timestamp('paid_at')->nullable(); $t->timestamps();
  });
 }
 public function down(): void { Schema::dropIfExists('payment_transactions'); Schema::dropIfExists('payment_gateway_settings'); Schema::dropIfExists('assessment_attachments'); }
};
