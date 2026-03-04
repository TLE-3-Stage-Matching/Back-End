<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_placements', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->nullable();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('company_name', 255);
            $table->string('contact_email', 255)->nullable();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->string('status', 16)->nullable();
            $table->foreignId('coordinator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_placements');
    }
};
