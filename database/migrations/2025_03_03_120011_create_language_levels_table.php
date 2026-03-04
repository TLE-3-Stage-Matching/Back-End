<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('language_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_levels');
    }
};
