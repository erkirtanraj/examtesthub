<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('test_series', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->longText('description')->nullable();
            $table->string('access_type', 10)->default('free')->index();
            $table->decimal('price', 10, 2)->default(0);
            $table->char('currency', 3)->default('INR');
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->unsignedInteger('max_enrollments')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('test_series_exam', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_series_id')->constrained('test_series')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
            $table->unique(['test_series_id', 'exam_id']);
            $table->index(['test_series_id', 'position']);
        });

        Schema::create('test_series_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_series_id')->constrained('test_series')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 24)->default('pending_payment')->index();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->char('currency', 3)->default('INR');
            $table->string('payment_reference', 120)->nullable()->index();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['test_series_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_series_enrollments');
        Schema::dropIfExists('test_series_exam');
        Schema::dropIfExists('test_series');
    }
};
