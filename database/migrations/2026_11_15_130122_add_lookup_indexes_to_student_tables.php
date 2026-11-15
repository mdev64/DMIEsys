<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->index('course_id');
            $table->index('student_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->index('student_advisor');
            $table->index('batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropIndex(['course_id']);
            $table->dropIndex(['student_id']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['student_advisor']);
            $table->dropIndex(['batch']);
        });
    }
};
