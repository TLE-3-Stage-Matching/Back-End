<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // Drop old triggers if they exist
            DB::unprepared("DROP TRIGGER IF EXISTS chk_users_role_insert");
            DB::unprepared("DROP TRIGGER IF EXISTS chk_users_role_update");

            // Recreate triggers with extended role set
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
            // Drop old check constraint if present, then add the new one
            try {
                DB::statement("ALTER TABLE users DROP CONSTRAINT chk_users_role");
            } catch (\Throwable $e) {
                // Constraint might not exist yet on some environments; ignore
            }

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
            // Revert back to original three-role constraint
            DB::unprepared("DROP TRIGGER IF EXISTS chk_users_role_insert");
            DB::unprepared("DROP TRIGGER IF EXISTS chk_users_role_update");

            DB::unprepared("
                CREATE TRIGGER chk_users_role_insert
                BEFORE INSERT ON users
                FOR EACH ROW
                WHEN NEW.role NOT IN ('student', 'coordinator', 'company')
                BEGIN
                    SELECT RAISE(ABORT, 'Invalid role. Must be student, coordinator, or company.');
                END;
            ");

            DB::unprepared("
                CREATE TRIGGER chk_users_role_update
                BEFORE UPDATE ON users
                FOR EACH ROW
                WHEN NEW.role NOT IN ('student', 'coordinator', 'company')
                BEGIN
                    SELECT RAISE(ABORT, 'Invalid role. Must be student, coordinator, or company.');
                END;
            ");
        } else {
            try {
                DB::statement("ALTER TABLE users DROP CONSTRAINT chk_users_role");
            } catch (\Throwable $e) {
                // ignore if not present
            }

            DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN ('student', 'coordinator', 'company'))");
        }
    }
};

