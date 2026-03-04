<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('company_locations')->nullOnDelete();
            $table->string('title', 255);
            $table->integer('hours_per_week')->nullable();
            $table->text('description')->nullable();
            $table->text('offer_text')->nullable();
            $table->text('expectations_text')->nullable();
            $table->string('status', 32)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancies');
    }
};
