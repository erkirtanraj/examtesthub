<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('exam_attempts', function (Blueprint $t) {
   $t->id(); $t->foreignId('exam_id')->constrained()->restrictOnDelete(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
   $t->string('status',20)->default('in_progress')->index(); $t->json('question_order'); $t->timestamp('started_at'); $t->timestamp('expires_at')->index();
   $t->timestamp('submitted_at')->nullable(); $t->decimal('score',10,2)->default(0); $t->decimal('total_marks',10,2)->default(0);
   $t->unsignedInteger('correct_count')->default(0); $t->unsignedInteger('incorrect_count')->default(0); $t->unsignedInteger('unanswered_count')->default(0);
   $t->timestamps(); $t->index(['user_id','exam_id','status']);
  });
  Schema::create('exam_attempt_answers', function (Blueprint $t) {
   $t->id(); $t->foreignId('exam_attempt_id')->constrained()->cascadeOnDelete(); $t->foreignId('question_id')->constrained()->restrictOnDelete();
   $t->json('answer')->nullable(); $t->boolean('marked_for_review')->default(false); $t->boolean('is_correct')->nullable();
   $t->decimal('marks_awarded',10,2)->default(0); $t->timestamp('answered_at')->nullable(); $t->timestamps(); $t->unique(['exam_attempt_id','question_id']);
  });
 }
 public function down(): void { Schema::dropIfExists('exam_attempt_answers'); Schema::dropIfExists('exam_attempts'); }
};