<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ExamAttemptAnswer extends Model {
 protected $fillable=['exam_attempt_id','question_id','answer','marked_for_review','is_correct','marks_awarded','answered_at'];
 protected function casts(): array { return ['answer'=>'array','marked_for_review'=>'boolean','is_correct'=>'boolean','marks_awarded'=>'decimal:2','answered_at'=>'datetime']; }
 public function attempt(): BelongsTo { return $this->belongsTo(ExamAttempt::class,'exam_attempt_id'); }
 public function question(): BelongsTo { return $this->belongsTo(Question::class); }
}