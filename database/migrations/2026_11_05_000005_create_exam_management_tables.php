<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->longText('description')->nullable();
            $table->longText('instructions')->nullable();
            $table->unsignedInteger('duration_minutes');
            $table->unsignedInteger('max_attempts')->default(1);
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('exam_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->decimal('marks', 8, 2)->nullable();
            $table->decimal('negative_marks', 8, 2)->nullable();
            $table->timestamps();
            $table->unique(['exam_id', 'question_id']);
            $table->index(['exam_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_question');
        Schema::dropIfExists('exams');
    }
};
