<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('course_enrollment_id')->nullable()->after('test_series_enrollment_id')->constrained('course_enrollments')->cascadeOnDelete();
            $table->foreignId('test_series_enrollment_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_enrollment_id');
            $table->foreignId('test_series_enrollment_id')->nullable(false)->change();
        });
    }
};
