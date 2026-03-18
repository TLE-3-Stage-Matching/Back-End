<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bias_alerts', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->nullable();
            $table->string('alert_type', 32);
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vacancy_id')->nullable()->constrained()->nullOnDelete();
            $table->text('must_have_snapshot')->nullable();
            $table->integer('remaining_candidates')->nullable();
            $table->text('bias_tip')->nullable();
            $table->string('status', 16)->nullable();
            $table->foreignId('coordinator_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bias_alerts');
    }
};
