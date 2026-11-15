<?php

namespace Tests\Feature;

use App\Http\Livewire\DashboardNotifications;
use App\Mail\FacilityBookingRemindingForTO;
use App\Mail\FacilityBookingRemindingForUser;
use App\Models\AdvisoryComment;
use App\Models\BookingLab;
use App\Models\Lab;
use App\Models\Student;
use App\Notifications\GeneralNotificationJust;
use App\Traits\Notify;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class RelationAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_advisor_and_comment_relations_resolve(): void
    {
        $advisor = $this->createUser();
        $student = Student::create([
            'student_id' => 'E18001',
            'name' => 'Student One',
            'email' => 'e18001@email.com',
            'batch' => 'E18',
            'student_advisor' => $advisor->id,
        ]);

        $comment = new AdvisoryComment;
        $comment->student_id = $student->student_id;
        $comment->comment = 'Doing well';
        $comment->commented_by = $advisor->id;
        $comment->save();

        $this->assertTrue($student->getAdvisor->is($advisor));
        $this->assertCount(1, $advisor->getStudents);
        $this->assertCount(1, $student->getAdvisoryComments);
        $this->assertTrue($comment->hasStudent->is($student));
        $this->assertTrue($comment->hasAdvisor->is($advisor));
    }

    public function test_notify_hod_only_notifies_the_head_of_the_department(): void
    {
        $hod = $this->createUser('see notification', 'Head of the Department');
        $lecturer = $this->createPlainUser('lecturer');

        $this->notifier()->notifyHOD('Something happened');

        $this->assertSame(1, $hod->notifications()->count());
        $this->assertSame(0, $lecturer->notifications()->count());
    }

    public function test_notify_self_notifies_the_signed_in_user_and_not_the_first_user(): void
    {
        $firstUser = $this->createPlainUser('firstuser');
        $currentUser = $this->createUser();

        $this->actingAs($currentUser);
        $this->notifier()->notifySelf('Well done');

        $this->assertSame(1, $currentUser->notifications()->count());
        $this->assertSame(0, $firstUser->notifications()->count());
    }

    public function test_dashboard_notifications_load_only_the_visible_ones_and_refresh_without_looping(): void
    {
        $user = $this->createUser();
        for ($i = 0; $i < 12; $i++) {
            $user->notify(new GeneralNotificationJust('Notification '.$i));
        }

        Livewire::actingAs($user)
            ->test(DashboardNotifications::class)
            ->assertSet('all_notifications_count', 12)
            ->assertSet('unread_notifications_count', 12)
            ->assertSet('hasUnreadNotifications', true)
            ->assertCount('all_notifications', 10)
            ->assertNotDispatched('updateNotificationBadge')
            ->call('loadNotifications')
            ->assertNotDispatched('updateNotificationBadge')
            ->call('ReadAll')
            ->assertSet('unread_notifications_count', 0)
            ->assertSet('hasUnreadNotifications', false)
            ->assertDispatched('updateNotificationBadge')
            ->call('DeleteAll')
            ->assertSet('all_notifications_count', 0)
            ->assertSet('hasNotifications', false);

        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_dashboard_notifications_can_mark_and_delete_a_single_notification(): void
    {
        $user = $this->createUser();
        $user->notify(new GeneralNotificationJust('First'));
        $user->notify(new GeneralNotificationJust('Second'));
        $notificationId = $user->notifications()->first()->id;

        Livewire::actingAs($user)
            ->test(DashboardNotifications::class)
            ->call('MarkAsRead', $notificationId)
            ->assertSet('unread_notifications_count', 1)
            ->call('Delete', $notificationId)
            ->assertSet('all_notifications_count', 1);
    }

    public function test_facility_booking_reminder_is_sent_even_when_the_scheduler_starts_a_few_seconds_late(): void
    {
        Mail::fake();
        $this->travelTo(now()->setTime(8, 30, 7));

        $technicalOfficer = $this->createUser();
        $lab = Lab::create(['name' => 'CNC Lab', 'book_by_internal_members' => 1]);

        $this->createBooking($lab, '09:00:00', '09:59:00', $technicalOfficer->id);
        $this->createBooking($lab, '11:00:00', '11:59:00', $technicalOfficer->id);

        $this->artisan('RemindFacilityBooking')->assertExitCode(0);

        Mail::assertQueued(FacilityBookingRemindingForTO::class, 1);
        Mail::assertQueued(FacilityBookingRemindingForUser::class, 1);
    }

    private function createBooking(Lab $lab, string $startTime, string $endTime, int $technicalStaffId): BookingLab
    {
        $booking = new BookingLab;
        $booking->lab_id = $lab->id;
        $booking->date = now()->format('Y-m-d');
        $booking->start_time = $startTime;
        $booking->end_time = $endTime;
        $booking->from = 'internal';
        $booking->name = 'Requester';
        $booking->email = 'requester@email.com';
        $booking->department = 'DMIE';
        $booking->approved = 1;
        $booking->technical_staff = $technicalStaffId;
        $booking->save();

        return $booking;
    }

    private function notifier(): object
    {
        return new class
        {
            use Notify;
        };
    }
}
