<?php
use App\Http\Controllers\Admin\AdminUserManagementController;
use App\Http\Controllers\Admin\ExamManagementController;
use App\Http\Controllers\Admin\QuestionBankController;
use App\Http\Controllers\Admin\TestSeriesController as AdminTestSeriesController;
use App\Http\Controllers\Auth\PortalAuthenticationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Student\StudentExamController;
use App\Http\Controllers\Student\TestSeriesController as StudentTestSeriesController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
Route::get('/',fn()=>Inertia::render('Welcome'))->name('home');
Route::get('/language/{locale}',function(string $locale){abort_unless(in_array($locale,['en','hi'],true),404);session(['locale'=>$locale]);if(request()->user())request()->user()->forceFill(['preferred_locale'=>$locale])->save();return back();})->name('language.switch');
Route::middleware('guest')->group(function(){
 Route::get('/admin/login',[PortalAuthenticationController::class,'adminLoginPage'])->name('admin.login');
 Route::post('/admin/login',[PortalAuthenticationController::class,'adminLogin'])->middleware('throttle:5,1')->name('admin.login.store');
 Route::get('/student/login',[PortalAuthenticationController::class,'studentLoginPage'])->name('student.login');
 Route::post('/student/login',[PortalAuthenticationController::class,'studentLogin'])->middleware('throttle:5,1')->name('student.login.store');
 Route::get('/register',[PortalAuthenticationController::class,'registerPage'])->name('register');
 Route::post('/register',[PortalAuthenticationController::class,'register'])->middleware('throttle:5,1')->name('register.store');
});
Route::post('/logout',[PortalAuthenticationController::class,'logout'])->middleware('auth')->name('logout');
Route::middleware(['auth','active','role:super-admin|admin|content-manager|exam-manager'])->prefix('admin')->name('admin.')->group(function(){
 Route::get('/',fn()=>redirect()->route('admin.dashboard'));
 Route::get('/dashboard',fn()=>Inertia::render('Admin/Dashboard'))->name('dashboard');
 Route::middleware('permission:users.view')->group(function(){
  Route::get('/users',[AdminUserManagementController::class,'users'])->name('users.index');
  Route::get('/users/export',[AdminUserManagementController::class,'exportUsers'])->middleware('permission:users.export')->name('users.export');
  Route::get('/users/{user}',[AdminUserManagementController::class,'showUser'])->name('users.show');
 });
 Route::middleware('permission:users.manage')->group(function(){
  Route::post('/users',[AdminUserManagementController::class,'storeUser'])->name('users.store');
  Route::put('/users/{user}',[AdminUserManagementController::class,'updateUser'])->name('users.update');
  Route::patch('/users/{user}/status',[AdminUserManagementController::class,'toggleUserStatus'])->name('users.status');
 });
 Route::post('/users/import',[AdminUserManagementController::class,'importUsers'])->middleware(['permission:users.import','throttle:10,1'])->name('users.import');
 Route::middleware('permission:roles.manage')->group(function(){
  Route::get('/roles',[AdminUserManagementController::class,'roles'])->name('roles.index');
  Route::post('/roles',[AdminUserManagementController::class,'storeRole'])->name('roles.store');
  Route::put('/roles/{role}',[AdminUserManagementController::class,'updateRole'])->name('roles.update');
 });
 Route::middleware('permission:permissions.manage')->group(function(){
  Route::get('/permissions',[AdminUserManagementController::class,'permissions'])->name('permissions.index');
  Route::post('/permissions',[AdminUserManagementController::class,'storePermission'])->name('permissions.store');
 });
 Route::middleware('permission:audit.view')->get('/activity',[AdminUserManagementController::class,'activity'])->name('activity.index');
 Route::get('/profile',[AdminUserManagementController::class,'profile'])->middleware('permission:profile.manage')->name('profile.index');
 Route::put('/profile',[AdminUserManagementController::class,'updateProfile'])->middleware('permission:profile.manage')->name('profile.update');
 Route::put('/profile/password',[AdminUserManagementController::class,'updatePassword'])->middleware('permission:profile.manage')->name('profile.password');
 Route::middleware('permission:questions.manage')->group(function(){
  Route::get('/questions',[QuestionBankController::class,'index'])->name('questions.index');
  Route::get('/questions/export',[QuestionBankController::class,'export'])->name('questions.export');
  Route::post('/questions/import',[QuestionBankController::class,'import'])->middleware('throttle:10,1')->name('questions.import');
  Route::post('/questions',[QuestionBankController::class,'store'])->name('questions.store');
  Route::put('/questions/{question}',[QuestionBankController::class,'update'])->name('questions.update');
  Route::delete('/questions/{question}',[QuestionBankController::class,'destroy'])->name('questions.destroy');
  Route::get('/subjects',[QuestionBankController::class,'subjects'])->name('subjects.index');
  Route::post('/subjects',[QuestionBankController::class,'storeSubject'])->name('subjects.store');
  Route::put('/subjects/{subject}',[QuestionBankController::class,'updateSubject'])->name('subjects.update');
  Route::post('/subjects/{subject}/topics',[QuestionBankController::class,'storeTopic'])->name('topics.store');
 });
 Route::middleware('permission:courses.view')->group(function(){
  Route::get('/courses',[CourseManagementController::class,'index'])->name('courses.index');
  Route::get('/courses/{course}/builder',[CourseManagementController::class,'builder'])->name('courses.builder');
 });
 Route::middleware('permission:courses.manage')->group(function(){
  Route::post('/courses',[CourseManagementController::class,'store'])->name('courses.store');
  Route::put('/courses/{course}',[CourseManagementController::class,'update'])->name('courses.update');
  Route::patch('/courses/{course}/status',[CourseManagementController::class,'updateStatus'])->name('courses.status');
 });
 Route::middleware('permission:lessons.manage')->group(function(){
  Route::post('/courses/{course}/sections',[CourseManagementController::class,'storeSection'])->name('courses.sections.store');
  Route::put('/courses/{course}/sections/{section}',[CourseManagementController::class,'updateSection'])->name('courses.sections.update');
  Route::post('/courses/{course}/sections/{section}/chapters',[CourseManagementController::class,'storeChapter'])->name('courses.chapters.store');
  Route::put('/courses/{course}/chapters/{chapter}',[CourseManagementController::class,'updateChapter'])->name('courses.chapters.update');
  Route::post('/courses/{course}/chapters/{chapter}/lessons',[CourseManagementController::class,'storeLesson'])->name('courses.lessons.store');
  Route::put('/courses/{course}/lessons/{lesson}',[CourseManagementController::class,'updateLesson'])->name('courses.lessons.update');
  Route::post('/courses/{course}/lessons/{lesson}/resources',[CourseManagementController::class,'storeResource'])->name('courses.lessons.resources.store');
 });
 Route::middleware('permission:tests.manage')->group(function(){
  Route::get('/payments',[PaymentController::class,'adminIndex'])->name('payments.index');
  Route::get('/exams',[ExamManagementController::class,'index'])->name('exams.index');
  Route::post('/exams',[ExamManagementController::class,'store'])->name('exams.store');
  Route::put('/exams/{exam}',[ExamManagementController::class,'update'])->name('exams.update');
  Route::patch('/exams/{exam}/status',[ExamManagementController::class,'updateStatus'])->name('exams.status');
  Route::get('/test-series',[AdminTestSeriesController::class,'index'])->name('test-series.index');
  Route::post('/test-series',[AdminTestSeriesController::class,'store'])->name('test-series.store');
  Route::put('/test-series/{testSeries}',[AdminTestSeriesController::class,'update'])->name('test-series.update');
  Route::patch('/test-series/{testSeries}/status',[AdminTestSeriesController::class,'updateStatus'])->name('test-series.status');
  Route::get('/test-series/{testSeries}/enrollments',[AdminTestSeriesController::class,'enrollments'])->name('test-series.enrollments');
  Route::post('/test-series/{testSeries}/enrollments/{user}/activate',[AdminTestSeriesController::class,'activateEnrollment'])->name('test-series.enrollments.activate');
 });
});
Route::middleware(['auth','active','role:student'])->prefix('student')->name('student.')->group(function(){
 Route::middleware('permission:courses.view')->group(function(){
  Route::get('/courses',[CourseController::class,'index'])->name('courses.index');
  Route::get('/courses/{course:slug}',[CourseController::class,'show'])->name('courses.show');
  Route::get('/courses/{course:slug}/lessons/{lesson}',[CourseController::class,'lesson'])->name('courses.lesson');
  Route::post('/courses/{course:slug}/enroll',[CourseController::class,'enroll'])->middleware('throttle:10,1')->name('courses.enroll');
  Route::post('/courses/{course:slug}/checkout',[PaymentController::class,'createCourseOrder'])->middleware('throttle:5,1')->name('courses.checkout');
  Route::post('/courses/{course:slug}/lessons/{lesson}/complete',[CourseController::class,'complete'])->middleware('throttle:30,1')->name('courses.lessons.complete');
 });
 Route::get('/payments',[PaymentController::class,'studentIndex'])->name('payments.index');
 Route::post('/test-series/{testSeries}/checkout',[PaymentController::class,'createOrder'])->middleware('throttle:5,1')->name('payments.create');
 Route::get('/payments/{payment}/checkout',[PaymentController::class,'checkout'])->name('payments.checkout');
 Route::post('/payments/{payment}/verify',[PaymentController::class,'verify'])->middleware('throttle:10,1')->name('payments.verify');
 Route::get('/dashboard',fn()=>Inertia::render('Student/Dashboard'))->name('dashboard');
 Route::get('/test-series',[StudentTestSeriesController::class,'index'])->name('test-series.index');
 Route::get('/my-test-series',[StudentTestSeriesController::class,'mySeries'])->name('test-series.mine');
 Route::post('/test-series/{testSeries}/enroll',[StudentTestSeriesController::class,'enroll'])->middleware('throttle:10,1')->name('test-series.enroll');
 Route::get('/exams',[StudentExamController::class,'index'])->name('exams.index');
 Route::post('/exams/{exam}/start',[StudentExamController::class,'start'])->middleware('throttle:10,1')->name('exams.start');
 Route::get('/attempts/{attempt}',[StudentExamController::class,'show'])->name('exams.attempt');
 Route::put('/attempts/{attempt}/answers/{question}',[StudentExamController::class,'saveAnswer'])->middleware('throttle:120,1')->name('exams.answers.save');
 Route::post('/attempts/{attempt}/submit',[StudentExamController::class,'submit'])->middleware('throttle:10,1')->name('exams.submit');
 Route::get('/attempts/{attempt}/result',[StudentExamController::class,'result'])->name('exams.result');
});