<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_saved_vacancies', function (Blueprint $table) {
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vacancy_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->primary(['student_user_id', 'vacancy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_saved_vacancies');
    }
};
