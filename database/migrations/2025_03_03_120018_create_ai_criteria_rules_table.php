<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_criteria_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criteria_version_id')->constrained('ai_criteria_versions')->cascadeOnDelete();
            $table->string('feature_type', 32); // skill_tags, trait_tags, education_tags, etc.
            $table->double('weight');
            $table->integer('min_required')->nullable();
            $table->double('penalty_if_missing')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_criteria_rules');
    }
};
