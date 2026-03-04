<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_overrides', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->nullable();
            $table->foreignId('coordinator_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vacancy_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 16);
            $table->text('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_overrides');
    }
};
