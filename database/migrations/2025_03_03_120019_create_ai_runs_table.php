<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('model_id');
            $table->foreignId('prompt_id')->nullable()->constrained('ai_prompts')->nullOnDelete();
            $table->string('run_type', 32);
            $table->foreignId('criteria_version_id')->nullable()->constrained('ai_criteria_versions')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_runs');
    }
};
