<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ExamAttempt extends Model {
 protected $fillable=['exam_id','user_id','status','question_order','started_at','expires_at','submitted_at','score','total_marks','correct_count','incorrect_count','unanswered_count'];
 protected function casts(): array { return ['question_order'=>'array','started_at'=>'datetime','expires_at'=>'datetime','submitted_at'=>'datetime','score'=>'decimal:2','total_marks'=>'decimal:2']; }
 public function exam(): BelongsTo { return $this->belongsTo(Exam::class); }
 public function user(): BelongsTo { return $this->belongsTo(User::class); }
 public function answers(): HasMany { return $this->hasMany(ExamAttemptAnswer::class); }
}