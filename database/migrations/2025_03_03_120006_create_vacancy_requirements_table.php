<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancy_requirements', function (Blueprint $table) {
            $table->foreignId('vacancy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->string('requirement_type', 16);
            $table->integer('importance')->nullable();
            $table->timestamps();
            $table->primary(['vacancy_id', 'tag_id', 'requirement_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancy_requirements');
    }
};
