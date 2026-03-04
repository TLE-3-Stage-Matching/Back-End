<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_match_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vacancy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_run_id')->nullable()->constrained('ai_runs')->nullOnDelete();
            $table->foreignId('source_match_score_id')->nullable()->constrained('match_vacancy_scores')->nullOnDelete();
            $table->string('status', 32); // shortlisted, requested, approved, rejected, withdrawn
            $table->text('student_note')->nullable();
            $table->timestamps();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_match_choices');
    }
};
