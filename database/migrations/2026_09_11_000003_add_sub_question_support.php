<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('question_results')) {
            Schema::create('question_results', function (Blueprint $table) {
                $table->id();
                $table->foreignId('assessment_id')->nullable();
                $table->string('question_number');
                $table->string('question_part')->nullable();
                $table->string('parent_question_number')->nullable();
                $table->decimal('max_marks', 10, 2)->default(0);
                $table->decimal('ai_marks', 10, 2)->default(0);
                $table->decimal('final_marks', 10, 2)->default(0);
                $table->decimal('confidence', 5, 2)->nullable();
                $table->text('feedback')->nullable();
                $table->text('teacher_comment')->nullable();
                $table->timestamps();
            });
            return;
        }

        Schema::table('question_results', function (Blueprint $table) {
            if (!Schema::hasColumn('question_results', 'question_part')) {
                $table->string('question_part')->nullable();
            }
            if (!Schema::hasColumn('question_results', 'parent_question_number')) {
                $table->string('parent_question_number')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('question_results')) {
            Schema::table('question_results', function (Blueprint $table) {
                $columns = [];
                foreach (['question_part','parent_question_number'] as $column) {
                    if (Schema::hasColumn('question_results',$column)) {
                        $columns[]=$column;
                    }
                }
                if ($columns) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
