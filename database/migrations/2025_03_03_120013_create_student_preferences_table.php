<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_preferences', function (Blueprint $table) {
            $table->foreignId('student_user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->foreignId('desired_role_tag_id')->nullable()->constrained('tags')->nullOnDelete();
            $table->integer('hours_per_week_min')->nullable();
            $table->integer('hours_per_week_max')->nullable();
            $table->integer('max_distance_km')->nullable();
            $table->boolean('has_drivers_license')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_preferences');
    }
};
