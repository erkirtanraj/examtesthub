<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->string('title_hi', 180)->nullable();
            $table->string('slug', 220)->unique();
            $table->longText('description')->nullable();
            $table->longText('description_hi')->nullable();
            $table->string('category', 120)->nullable()->index();
            $table->string('access_type', 10)->default('free')->index();
            $table->decimal('price', 10, 2)->default(0);
            $table->char('currency', 3)->default('INR');
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('course_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('title_hi', 180)->nullable();
            $table->text('description')->nullable();
            $table->text('description_hi')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
            $table->index(['course_id', 'position']);
        });

        Schema::create('course_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('course_sections')->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('title_hi', 180)->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
            $table->index(['section_id', 'position']);
        });

        Schema::create('course_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('course_chapters')->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('title_hi', 180)->nullable();
            $table->longText('description')->nullable();
            $table->longText('description_hi')->nullable();
            $table->string('type', 16)->default('text');
            $table->text('video_url')->nullable();
            $table->longText('content')->nullable();
            $table->longText('content_hi')->nullable();
            $table->boolean('is_preview')->default(false);
            $table->unsignedSmallInteger('duration_minutes')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
            $table->index(['chapter_id', 'position']);
        });

        Schema::create('course_lesson_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('course_lessons')->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('title_hi', 180)->nullable();
            $table->string('type', 12)->default('link');
            $table->string('url', 2048)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
            $table->index(['lesson_id', 'position']);
        });

        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 24)->default('pending_payment')->index();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->char('currency', 3)->default('INR');
            $table->string('payment_reference', 120)->nullable()->index();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('course_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_enrollment_id')->constrained('course_enrollments')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('course_lessons')->cascadeOnDelete();
            $table->string('status', 20)->default('not_started');
            $table->unsignedInteger('progress_seconds')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['course_enrollment_id', 'lesson_id']);
            $table->index(['lesson_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_lesson_progress');
        Schema::dropIfExists('course_enrollments');
        Schema::dropIfExists('course_lesson_resources');
        Schema::dropIfExists('course_lessons');
        Schema::dropIfExists('course_chapters');
        Schema::dropIfExists('course_sections');
        Schema::dropIfExists('courses');
    }
};
