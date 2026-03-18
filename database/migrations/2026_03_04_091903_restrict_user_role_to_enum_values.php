<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER TABLE ADD CONSTRAINT, so use triggers
            DB::unprepared("
                CREATE TRIGGER chk_users_role_insert
                BEFORE INSERT ON users
                FOR EACH ROW
                WHEN NEW.role NOT IN ('student', 'coordinator', 'company', 'admin', 'dev')
                BEGIN
                    SELECT RAISE(ABORT, 'Invalid role. Must be student, coordinator, company, admin, or dev.');
                END;
            ");

            DB::unprepared("
                CREATE TRIGGER chk_users_role_update
                BEFORE UPDATE ON users
                FOR EACH ROW
                WHEN NEW.role NOT IN ('student', 'coordinator', 'company', 'admin', 'dev')
                BEGIN
                    SELECT RAISE(ABORT, 'Invalid role. Must be student, coordinator, company, admin, or dev.');
                END;
            ");
        } else {
            DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN ('student', 'coordinator', 'company', 'admin', 'dev'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared("DROP TRIGGER IF EXISTS chk_users_role_insert");
            DB::unprepared("DROP TRIGGER IF EXISTS chk_users_role_update");
        } else {
            DB::statement("ALTER TABLE users DROP CONSTRAINT chk_users_role");
        }
    }
};
