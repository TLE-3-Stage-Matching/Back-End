<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['name', 'email_verified_at', 'remember_token']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->after('id');
            $table->string('first_name', 100)->after('password');
            $table->string('middle_name', 100)->nullable()->after('first_name');
            $table->string('last_name', 100)->after('middle_name');
            $table->string('phone', 50)->nullable()->after('last_name');
            $table->text('profile_photo_url')->nullable()->after('phone');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('password', 'password_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('password_hash', 'password');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role', 'first_name', 'middle_name', 'last_name',
                'phone', 'profile_photo_url'
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
        });
    }
};
