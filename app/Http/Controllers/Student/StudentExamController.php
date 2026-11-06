<?php
namespace App\Http\Controllers\Student;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
class StudentExamController extends Controller {
 public function index(Request $request): Response {
  $now=now();
  $exams=Exam::whereIn('exams.status',['published','scheduled'])
   ->where(fn($q)=>$q->whereNull('exams.starts_at')->orWhere('exams.starts_at','<=',$now))
   ->where(fn($q)=>$q->whereNull('exams.ends_at')->orWhere('exams.ends_at','>',$now))
   ->where(fn($q)=>$q->whereDoesntHave('testSeries')->orWhereHas('testSeries',fn($s)=>$s->where('test_series.status','published')->where(fn($w)=>$w->whereNull('test_series.starts_at')->orWhere('test_series.starts_at','<=',$now))->where(fn($w)=>$w->whereNull('test_series.ends_at')->orWhere('test_series.ends_at','>',$now))->whereHas('enrollments',fn($en)=>$en->where('user_id',$request->user()->id)->where('status','active'))))
   ->withCount('questions')->with(['attempts'=>fn($q)=>$q->where('user_id',$request->user()->id)->latest()])
   ->orderBy('title')->paginate(12)->through(fn($e)=>[
    'id'=>$e->id,'title'=>$e->title,'description'=>$e->description,'duration_minutes'=>$e->duration_minutes,
    'max_attempts'=>$e->max_attempts,'questions_count'=>$e->questions_count,'attempts_used'=>$e->attempts->count(),
    'latest_attempt'=>$e->attempts->first()?['id'=>$e->attempts->first()->id,'status'=>$e->attempts->first()->status,'score'=>$e->attempts->first()->score]:null
   ]);
  return Inertia::render('Student/Exams/Index',['exams'=>$exams]);
 }
 public function start(Request $request, Exam $exam) {
  $attempt=DB::transaction(function() use($request,$exam) {
   $e=Exam::whereKey($exam->id)->lockForUpdate()->firstOrFail(); $now=now();
   abort_unless(in_array($e->status,['published','scheduled'],true),404);
   if($e->testSeries()->exists()){ $allowed=$e->testSeries()->where('test_series.status','published')->where(fn($w)=>$w->whereNull('test_series.starts_at')->orWhere('test_series.starts_at','<=',$now))->where(fn($w)=>$w->whereNull('test_series.ends_at')->orWhere('test_series.ends_at','>',$now))->whereHas('enrollments',fn($en)=>$en->where('user_id',$request->user()->id)->where('status','active'))->exists(); abort_unless($allowed,403,'An active test-series enrollment is required.'); }
   abort_if($e->starts_at && $e->starts_at->isFuture(),403,'Exam has not started.');
   abort_if($e->ends_at && $e->ends_at->lessThanOrEqualTo($now),403,'Exam has ended.');
   $active=ExamAttempt::where('exam_id',$e->id)->where('user_id',$request->user()->id)->where('status','in_progress')->latest()->first();
   if($active) return $active;
   abort_if(ExamAttempt::where('exam_id',$e->id)->where('user_id',$request->user()->id)->count()>=$e->max_attempts,403,'Maximum attempts reached.');
   $ids=$e->questions()->where('status','published')->pluck('questions.id')->all(); abort_if(!$ids,422,'No published questions available.');
   if($e->shuffle_questions) shuffle($ids);
   $expires=$now->copy()->addMinutes($e->duration_minutes); if($e->ends_at && $e->ends_at->lt($expires)) $expires=$e->ends_at->copy();
   $a=ExamAttempt::create(['exam_id'=>$e->id,'user_id'=>$request->user()->id,'status'=>'in_progress','question_order'=>$ids,'started_at'=>$now,'expires_at'=>$expires,'total_marks'=>$e->questions()->sum('exam_question.marks')]);
   foreach($ids as $id) $a->answers()->create(['question_id'=>$id]);
   return $a;
  });
  return redirect()->route('student.exams.attempt',$attempt);
 }
 public function show(Request $request, ExamAttempt $attempt): Response|\Illuminate\Http\RedirectResponse {
  $this->owner($request,$attempt);
  if($attempt->status!=='in_progress') return redirect()->route('student.exams.result',$attempt);
  if($attempt->expires_at->isPast()) { $this->grade($attempt); return redirect()->route('student.exams.result',$attempt); }
  $exam=$attempt->exam; $qs=Question::whereIn('id',$attempt->question_order)->get()->keyBy('id'); $saved=$attempt->answers->keyBy('question_id');
  $questions=collect($attempt->question_order)->map(function($id)use($qs,$saved,$exam){
   $q=$qs->get($id); if(!$q)return null; $a=$saved->get($id);
   $pivot=DB::table('exam_question')->where('exam_id',$exam->id)->where('question_id',$id)->first();
   return ['id'=>$q->id,'type'=>$q->type,'prompt'=>$q->prompt,'options'=>$q->options??[],
    'marks'=>$pivot?->marks??$q->marks,'answer'=>$a?->answer,'marked_for_review'=>$a ? (bool) $a->marked_for_review : false];
  })->filter()->values();
  return Inertia::render('Student/Exams/Attempt',['attempt'=>['id'=>$attempt->id,'expires_at'=>$attempt->expires_at->toIso8601String()],
   'exam'=>['title'=>$exam->title,'instructions'=>$exam->instructions],'questions'=>$questions]);
 }
 public function saveAnswer(Request $request, ExamAttempt $attempt, Question $question) {
  $data=$request->validate([
   'answer'=>['nullable','array','max:20'],
   'answer.*'=>['nullable','string','max:2000'],
   'marked_for_review'=>['sometimes','boolean'],
  ]);

  $answer=array_values(array_filter($data['answer']??[],fn($value)=>$value!==null&&$value!==''));
  $options=$question->options??[];
  if(in_array($question->type,['single_choice','true_false'],true)){
   abort_if(count($answer)>1,422,'This question accepts only one answer.');
   foreach($answer as $choice) abort_unless(array_key_exists($choice,$options),422,'The selected option is invalid.');
  }elseif($question->type==='multiple_choice'){
   foreach($answer as $choice) abort_unless(array_key_exists($choice,$options),422,'One or more selected options are invalid.');
  }elseif($question->type==='short_answer'){
   abort_if(count($answer)>1,422,'This question accepts one text answer.');
  }else{
   abort(422,'Unsupported question type.');
  }

  $saved=DB::transaction(function()use($request,$attempt,$question,$data,$answer){
   $locked=ExamAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
   $this->owner($request,$locked);
   abort_unless(collect($locked->question_order??[])->contains(fn($id)=>(int)$id===$question->id),404);
   abort_if($locked->status!=='in_progress',422,'Attempt already submitted.');

   // Return from the transaction before grading: aborting inside this transaction
   // would roll back the expiry submission as well.
   if($locked->expires_at->isPast()) return false;

   $locked->answers()->updateOrCreate(
    ['question_id'=>$question->id],
    [
     'answer'=>count($answer)>0?$answer:null,
     'marked_for_review'=>$data['marked_for_review']??false,
     'answered_at'=>count($answer)>0?now():null,
    ]
   );

   return true;
  });

  if(!$saved){
   $this->grade($attempt);
   abort(422,'Time expired; attempt submitted.');
  }

  return back(303);
 }
 public function submit(Request $request, ExamAttempt $attempt) {
  $this->owner($request,$attempt); if($attempt->status==='in_progress')$this->grade($attempt);
  return redirect()->route('student.exams.result',$attempt);
 }
 public function result(Request $request, ExamAttempt $attempt): Response {
  $this->owner($request,$attempt); abort_unless($attempt->status==='submitted',404); $attempt->load(['exam','answers.question']);
  return Inertia::render('Student/Exams/Result',['exam'=>['title'=>$attempt->exam->title],
   'result'=>['score'=>$attempt->score,'total_marks'=>$attempt->total_marks,'correct_count'=>$attempt->correct_count,'incorrect_count'=>$attempt->incorrect_count,'unanswered_count'=>$attempt->unanswered_count],
   'answers'=>$attempt->answers->map(fn($a)=>['prompt'=>$a->question->prompt,'answer'=>$a->answer,'correct_answer'=>$a->question->correct_answer,'explanation'=>$a->question->explanation,'is_correct'=>$a->is_correct,'marks_awarded'=>$a->marks_awarded])]);
 }
 private function owner(Request $r, ExamAttempt $a): void { abort_unless($a->user_id===$r->user()->id,404); }
 private function active(ExamAttempt $a): void {
  abort_if($a->status!=='in_progress',422,'Attempt already submitted.');
  if($a->expires_at->isPast()){ $this->grade($a); abort(422,'Time expired; attempt submitted.'); }
 }
 private function grade(ExamAttempt $attempt): void {
  $submitted = DB::transaction(function()use($attempt){
   $a=ExamAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail(); if($a->status!=='in_progress')return false;
   $a->load(['answers.question']); $correct=0;$wrong=0;$blank=0;$score=0;
   foreach($a->answers as $row){
    $given=collect($row->answer??[])->filter(fn($v)=>$v!==null&&$v!=='')->map(fn($v)=>trim((string)$v))->sort()->values()->all();
    $expected=collect($row->question->correct_answer??[])->map(fn($v)=>trim((string)$v))->sort()->values()->all();
    $has=(bool)count($given);$ok=$has&&$given===$expected;
    $p=DB::table('exam_question')->where('exam_id',$a->exam_id)->where('question_id',$row->question_id)->first();
    $marks=(float)($p?->marks??$row->question->marks);$negative=(float)($p?->negative_marks??$row->question->negative_marks);
    $awarded=$ok?$marks:($has?-$negative:0); if($ok)$correct++;elseif($has)$wrong++;else$blank++;$score+=$awarded;
    $row->update(['is_correct'=>$has?$ok:null,'marks_awarded'=>$awarded]);
   }
   $a->update(['status'=>'submitted','submitted_at'=>now(),'score'=>$score,'correct_count'=>$correct,'incorrect_count'=>$wrong,'unanswered_count'=>$blank]);
   return true;
  });
  if ($submitted) {
   $user = User::find($attempt->user_id);
   $preferences = $user?->notification_preferences ?? [];
   if ($user && ($preferences['exam_results'] ?? true)) {
    $user->notify(new PlatformNotification('Exam result available', 'Your exam has been submitted and your result is ready to view.', '/student/attempts/'.$attempt->id.'/result', 'exam_result'));
   }
  }
 }
}