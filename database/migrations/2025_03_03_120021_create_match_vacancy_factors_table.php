<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_vacancy_factors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_vacancy_score_id')->constrained()->cascadeOnDelete();
            $table->string('factor_label', 255);
            $table->integer('impact');
            $table->string('polarity', 16);
            $table->string('factor_type', 16)->nullable();
            $table->foreignId('tag_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_vacancy_factors');
    }
};
