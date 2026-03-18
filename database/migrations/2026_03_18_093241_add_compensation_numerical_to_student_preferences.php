<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('student_preferences', function (Blueprint $table) {
            $table->unsignedTinyInteger('compensation_numerical')->nullable();
        });
    }

    public function down()
    {
        Schema::table('student_preferences', function (Blueprint $table) {
            $table->dropColumn('compensation_numerical');
        });
    }
};
