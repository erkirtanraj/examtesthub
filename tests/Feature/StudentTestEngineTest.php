<?php
namespace Tests\Feature;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
class StudentTestEngineTest extends TestCase {
 use RefreshDatabase;
 protected function setUp(): void { parent::setUp(); $this->withoutVite(); }
 private function user(string $role,string $email): User {
  $r=Role::firstOrCreate(['slug'=>$role],['name'=>ucfirst($role)]);
  $u=User::create(['name'=>'Test User','email'=>$email,'password'=>Hash::make('StrongPassword!2026'),'is_active'=>true]);
  $u->roles()->attach($r); return $u;
 }
 private function exam(User $admin): Exam {
  $s=Subject::create(['name'=>'Math','slug'=>'math']);
  $q=Question::create(['subject_id'=>$s->id,'created_by'=>$admin->id,'type'=>'single_choice','prompt'=>'2 + 2?',
   'options'=>['A'=>'3','B'=>'4'],'correct_answer'=>['B'],'difficulty'=>'easy','language'=>'en','marks'=>2,'negative_marks'=>0.5,'status'=>'published']);
  $e=Exam::create(['title'=>'Math test','slug'=>'math-test-'.uniqid(),'duration_minutes'=>30,'max_attempts'=>1,
   'shuffle_questions'=>false,'shuffle_options'=>false,'status'=>'published','created_by'=>$admin->id]);
  $e->questions()->attach($q->id,['position'=>1,'marks'=>2,'negative_marks'=>0.5]); return $e;
 }
 public function test_student_can_start_save_and_submit_with_server_grading(): void {
  $admin=$this->user('super-admin','engine-admin@example.test');$student=$this->user('student','engine-student@example.test');$e=$this->exam($admin);
  $this->actingAs($student)->post('/student/exams/'.$e->id.'/start')->assertRedirect();
  $a=ExamAttempt::firstOrFail();$q=$e->questions()->first();
  $this->actingAs($student)->put('/student/attempts/'.$a->id.'/answers/'.$q->id,['answer'=>['B'],'marked_for_review'=>true])->assertRedirect();
  $this->actingAs($student)->post('/student/attempts/'.$a->id.'/submit')->assertRedirect('/student/attempts/'.$a->id.'/result');
  $this->assertDatabaseHas('exam_attempts',['id'=>$a->id,'status'=>'submitted','correct_count'=>1,'score'=>2]);
 }
 public function test_negative_marks_are_applied_for_wrong_answers(): void {
  $admin=$this->user('super-admin','engine-admin2@example.test');$student=$this->user('student','engine-student2@example.test');$e=$this->exam($admin);
  $this->actingAs($student)->post('/student/exams/'.$e->id.'/start');$a=ExamAttempt::firstOrFail();$q=$e->questions()->first();
  $this->actingAs($student)->put('/student/attempts/'.$a->id.'/answers/'.$q->id,['answer'=>['A']]);
  $this->actingAs($student)->post('/student/attempts/'.$a->id.'/submit');
  $this->assertDatabaseHas('exam_attempts',['id'=>$a->id,'score'=>-0.5,'incorrect_count'=>1]);
 }
 public function test_student_cannot_access_another_students_attempt(): void {
  $admin=$this->user('super-admin','engine-admin3@example.test');$student=$this->user('student','engine-student3@example.test');$other=$this->user('student','engine-other@example.test');$e=$this->exam($admin);
  $this->actingAs($student)->post('/student/exams/'.$e->id.'/start');$a=ExamAttempt::firstOrFail();
  $this->actingAs($other)->get('/student/attempts/'.$a->id)->assertNotFound();
 }
 public function test_student_cannot_exceed_max_attempts(): void {
  $admin=$this->user('super-admin','engine-admin4@example.test');$student=$this->user('student','engine-student4@example.test');$e=$this->exam($admin);
  $this->actingAs($student)->post('/student/exams/'.$e->id.'/start');$a=ExamAttempt::firstOrFail();
  $this->actingAs($student)->post('/student/attempts/'.$a->id.'/submit');
  $this->actingAs($student)->post('/student/exams/'.$e->id.'/start')->assertForbidden();
  $this->assertDatabaseCount('exam_attempts',1);
 }
 public function test_expired_attempt_is_auto_submitted_when_reopened(): void {
  $admin=$this->user('super-admin','engine-admin5@example.test');$student=$this->user('student','engine-student5@example.test');$e=$this->exam($admin);
  $this->actingAs($student)->post('/student/exams/'.$e->id.'/start');$a=ExamAttempt::firstOrFail();$a->update(['expires_at'=>now()->subSecond()]);
  $this->actingAs($student)->get('/student/attempts/'.$a->id)->assertRedirect('/student/attempts/'.$a->id.'/result');
  $this->assertDatabaseHas('exam_attempts',['id'=>$a->id,'status'=>'submitted']);
 }
 public function test_student_cannot_save_an_option_that_does_not_exist(): void {
  $admin=$this->user('super-admin','engine-invalid-admin@example.test');$student=$this->user('student','engine-invalid-student@example.test');$e=$this->exam($admin);
  $this->actingAs($student)->post('/student/exams/'.$e->id.'/start');
  $a=ExamAttempt::firstOrFail();$q=$e->questions()->first();
  $this->actingAs($student)->put('/student/attempts/'.$a->id.'/answers/'.$q->id,['answer'=>['Z']])->assertStatus(422);
  $this->assertDatabaseHas('exam_attempt_answers',['exam_attempt_id'=>$a->id,'question_id'=>$q->id,'answer'=>null]);
 }
 public function test_expired_attempt_cannot_accept_a_late_answer_and_is_submitted(): void {
  $admin=$this->user('super-admin','engine-expired-admin@example.test');$student=$this->user('student','engine-expired-student@example.test');$e=$this->exam($admin);
  $this->actingAs($student)->post('/student/exams/'.$e->id.'/start');
  $a=ExamAttempt::firstOrFail();$q=$e->questions()->first();$a->update(['expires_at'=>now()->subSecond()]);
  $this->actingAs($student)->put('/student/attempts/'.$a->id.'/answers/'.$q->id,['answer'=>['B']])->assertStatus(422);
  $this->assertDatabaseHas('exam_attempts',['id'=>$a->id,'status'=>'submitted']);
  $this->assertDatabaseHas('exam_attempt_answers',['exam_attempt_id'=>$a->id,'question_id'=>$q->id,'answer'=>null]);
 }

}
