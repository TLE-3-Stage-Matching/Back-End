<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_languages', function (Blueprint $table) {
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_level_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->nullable();
            $table->timestamps();
            $table->primary(['student_user_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_languages');
    }
};
