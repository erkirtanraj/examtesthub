<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function user(string $role, string $email, array $permissions = []): User
    {
        $roleModel = Role::firstOrCreate(['slug' => $role], ['name' => ucfirst($role)]);
        $user = User::create(['name' => 'Test '.$role, 'email' => $email, 'password' => Hash::make('StrongPassword!2026'), 'is_active' => true]);
        $user->roles()->attach($roleModel);
        if ($permissions) {
            $ids = collect($permissions)->map(fn ($slug) => Permission::firstOrCreate(['slug' => $slug], ['name' => str($slug)->replace('.', ' ')->headline()->toString()])->id)->all();
            $roleModel->permissions()->syncWithoutDetaching($ids);
        }
        return $user;
    }

    public function test_student_can_view_only_their_own_notifications_and_unread_count(): void
    {
        $student = $this->user('student', 'notice-owner@example.test');
        $other = $this->user('student', 'notice-other@example.test');
        $student->notifyNow(new PlatformNotification('Welcome', 'Your account is ready.'));
        $other->notifyNow(new PlatformNotification('Private', 'This belongs to another student.'));

        $this->actingAs($student)->get('/student/notifications')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Notifications/Index')
                ->where('unreadCount', 1)
                ->where('notifications.total', 1)
                ->where('notifications.data.0.title', 'Welcome'));
    }

    public function test_student_can_mark_one_or_all_notifications_as_read(): void
    {
        $student = $this->user('student', 'notice-read@example.test');
        $student->notifyNow(new PlatformNotification('One', 'First'));
        $student->notifyNow(new PlatformNotification('Two', 'Second'));
        $notification = $student->notifications()->firstOrFail();

        $this->actingAs($student)->patch('/student/notifications/'.$notification->id.'/read')->assertRedirect();
        $this->assertNotNull($student->notifications()->whereKey($notification->id)->first()->read_at);

        $this->actingAs($student)->post('/student/notifications/read-all')->assertRedirect();
        $this->assertSame(0, $student->fresh()->unreadNotifications()->count());
    }

    public function test_student_can_update_notification_preferences(): void
    {
        $student = $this->user('student', 'notice-prefs@example.test');

        $this->actingAs($student)->put('/student/notification-preferences', [
            'email' => true,
            'announcements' => false,
            'exam_results' => true,
            'course_updates' => false,
        ])->assertRedirect();

        $this->assertSame([
            'email' => true,
            'announcements' => false,
            'exam_results' => true,
            'course_updates' => false,
        ], $student->fresh()->notification_preferences);
    }

    public function test_admin_can_send_announcement_to_active_students(): void
    {
        Notification::fake();
        $admin = $this->user('admin', 'notice-admin@example.test', ['settings.manage']);
        $student = $this->user('student', 'notice-target@example.test');
        $inactive = $this->user('student', 'notice-inactive@example.test');
        $inactive->update(['is_active' => false]);

        $this->actingAs($admin)->post('/admin/announcements', [
            'title' => 'Exam schedule',
            'message' => 'The exam schedule has been published.',
            'audience' => 'all',
            'send_email' => false,
        ])->assertRedirect();

        Notification::assertSentTo($student, PlatformNotification::class);
        Notification::assertNotSentTo($inactive, PlatformNotification::class);
    }

    public function test_students_cannot_send_announcements_and_admin_requires_permission(): void
    {
        $student = $this->user('student', 'notice-no-send@example.test');
        $this->actingAs($student)->post('/admin/announcements', [
            'title' => 'No access', 'message' => 'Not allowed', 'audience' => 'all',
        ])->assertForbidden();

        $admin = $this->user('admin', 'notice-no-permission@example.test');
        $this->actingAs($admin)->get('/admin/announcements')->assertForbidden();
    }

    public function test_notification_preferences_control_email_channel(): void
    {
        $student = $this->user('student', 'notice-email@example.test');
        $notification = new PlatformNotification('Result', 'Your result is ready.');
        $this->assertSame(['database'], $notification->via($student));

        $student->forceFill(['notification_preferences' => ['email' => true]])->save();
        $this->assertSame(['database', 'mail'], $notification->via($student->fresh()));
    }
}
