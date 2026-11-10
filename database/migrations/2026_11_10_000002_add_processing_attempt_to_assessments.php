<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->uuid('processing_token')->nullable()->index();
            $table->uuid('processing_claim_token')->nullable();
        });
        Schema::table('question_results', fn (Blueprint $table) => $table->string('type')->default('normal'));
    }

    public function down(): void
    {
        Schema::table('assessments', fn (Blueprint $table) => $table->dropColumn(['processing_token', 'processing_claim_token']));
        Schema::table('question_results', fn (Blueprint $table) => $table->dropColumn('type'));
    }
};
