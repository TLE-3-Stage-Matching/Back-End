<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->foreignId('industry_tag_id')->nullable()->constrained('tags')->nullOnDelete();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('size_category', 50)->nullable();
            $table->text('photo_url')->nullable();
            $table->boolean('is_active')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
