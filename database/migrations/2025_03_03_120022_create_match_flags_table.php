<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_flags', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->nullable();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vacancy_id')->nullable()->constrained()->nullOnDelete();
            $table->text('disputed_factor');
            $table->text('message')->nullable();
            $table->string('status', 16)->nullable();
            $table->foreignId('coordinator_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_flags');
    }
};
