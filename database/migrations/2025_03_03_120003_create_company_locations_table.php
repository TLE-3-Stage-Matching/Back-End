<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->text('address_line')->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('city', 255);
            $table->string('country', 255);
            $table->double('lat')->nullable();
            $table->double('lon')->nullable();
            $table->boolean('is_primary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_locations');
    }
};
