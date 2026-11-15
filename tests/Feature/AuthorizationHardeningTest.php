<?php

namespace Tests\Feature;

use App\Http\Livewire\RolesList;
use App\Http\Livewire\UpdateUserRole;
use App\Models\BookingLab;
use App\Models\Lab;
use App\Models\Machine;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\Student;
use App\Models\User;
use Cmgmyr\Messenger\Models\Message;
use Cmgmyr\Messenger\Models\Participant;
use Cmgmyr\Messenger\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthorizationHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['honeypot.enabled' => false]);
    }

    public function test_user_cannot_read_or_reply_to_a_thread_they_are_not_part_of(): void
    {
        $outsider = $this->createUser('can chat');
        $thread = $this->createThread($this->createPlainUser('alice'), $this->createPlainUser('bob'));

        $this->actingAs($outsider)->get('/dmiesys/messages/'.$thread->id)->assertNotFound();
        $this->actingAs($outsider)->put('/dmiesys/messages/'.$thread->id, ['message' => 'Let me in'])->assertNotFound();

        $this->assertSame(0, Message::where('thread_id', $thread->id)->count());
        $this->assertFalse(in_array($outsider->id, $thread->participantsUserIds()));
    }

    public function test_participant_can_reply_to_their_own_thread(): void
    {
        $participant = $this->createUser('can chat');
        $thread = $this->createThread($participant, $this->createPlainUser('bob'));

        $this->actingAs($participant)
            ->put('/dmiesys/messages/'.$thread->id, ['message' => 'Hello'])
            ->assertSessionHas('toast_success');

        $this->assertSame(1, Message::where('thread_id', $thread->id)->count());
    }

    public function test_student_advisor_cannot_be_changed_without_permission(): void
    {
        $user = $this->createUser();
        $advisor = $this->createPlainUser('advisor');
        $student = Student::create(['student_id' => 'E18001', 'name' => 'Student One', 'email' => 'e18001@email.com', 'batch' => 'E18']);

        $this->actingAs($user)
            ->put('/dmiesys/student-affairs/student-advisor/1', ['student_id' => $student->student_id, 'advisor' => $advisor->id])
            ->assertNotFound();

        $this->assertNull($student->fresh()->student_advisor);
    }

    public function test_student_advisor_can_be_changed_with_permission(): void
    {
        $user = $this->createUser('add student advisor');
        $advisor = $this->createPlainUser('advisor');
        $student = Student::create(['student_id' => 'E18001', 'name' => 'Student One', 'email' => 'e18001@email.com', 'batch' => 'E18']);

        $this->actingAs($user)
            ->put('/dmiesys/student-affairs/student-advisor/1', ['student_id' => $student->student_id, 'advisor' => $advisor->id])
            ->assertSessionHas('toast_success');

        $this->assertEquals($advisor->id, $student->fresh()->student_advisor);
        $this->assertSame(1, $advisor->notifications()->count());
    }

    public function test_lab_cannot_be_double_booked_inside_an_existing_booking(): void
    {
        Mail::fake();
        $user = $this->createUser();
        $lab = Lab::create(['name' => 'CNC Lab', 'book_by_internal_members' => 1]);
        $date = now()->addDays(7)->format('Y-m-d');

        $existing = new BookingLab;
        $existing->lab_id = $lab->id;
        $existing->date = $date;
        $existing->start_time = '09:00:00';
        $existing->end_time = '11:59:00';
        $existing->from = 'internal';
        $existing->name = 'Someone';
        $existing->email = 'someone@email.com';
        $existing->department = 'DMIE';
        $existing->save();

        $this->actingAs($user)
            ->post('/dmiesys/book-facility/book-labs', ['lab' => $lab->id, 'date' => $date, 'start_time' => '10:00', 'end_time' => '10:29'])
            ->assertSessionHasErrors();
        $this->assertSame(1, BookingLab::count());

        $this->actingAs($user)
            ->post('/dmiesys/book-facility/book-labs', ['lab' => $lab->id, 'date' => $date, 'start_time' => '13:00', 'end_time' => '13:29'])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, BookingLab::count());
    }

    public function test_maintenance_record_of_another_user_cannot_be_completed(): void
    {
        $user = $this->createUser('update maintenance');
        $owner = $this->createPlainUser('owner');

        $machine = Machine::create(['name' => 'Lathe', 'model' => 'L1', 'year_of_made' => '2015', 'date_of_purchased' => '2016-01-01']);
        $task = MaintenanceSchedule::create(['task' => 'Oil it', 'machine_id' => $machine->id, 'user_id' => $owner->id, 'period' => 1, 'WorM' => 'W', 'table_ref_no' => 'A1', 'next_run_date' => now()->toDateString()]);
        $record = MaintenanceRecord::create(['task_id' => $task->id, 'user_id' => $owner->id, 'machine_id' => $machine->id, 'due_date' => now()->toDateString()]);

        $this->actingAs($user)
            ->put('/dmiesys/manage-machines/maintenance-record/'.$record->id, ['comments' => 'Done by me'])
            ->assertNotFound();

        $this->assertNull($record->fresh()->comments);
    }

    public function test_roles_cannot_be_created_from_the_roles_list_without_permission(): void
    {
        $user = $this->createUser();

        Livewire::actingAs($user)
            ->test(RolesList::class)
            ->set('newRole', 'Sneaky Role')
            ->call('Submit')
            ->assertStatus(404);

        $this->assertFalse(Role::where('name', 'Sneaky Role')->exists());
    }

    public function test_user_role_cannot_be_changed_to_super_admin(): void
    {
        $user = $this->createUser('change user role');
        $target = $this->createPlainUser('target');
        Role::create(['name' => 'Super Admin']);

        Livewire::actingAs($user)
            ->test(UpdateUserRole::class)
            ->set('user', $target->id)
            ->set('role', 'Super Admin')
            ->call('submitForm')
            ->assertStatus(404);

        $this->assertFalse(User::find($target->id)->hasRole('Super Admin'));
    }

    public function test_external_user_request_for_an_unknown_student_fails_gracefully(): void
    {
        $this->post('/external-user', ['category' => 'undergraduate', 'batch' => 'E18', 'student_id' => 'E18999', 'email' => 'nobody@email.com'])
            ->assertRedirect()
            ->assertSessionHasErrors();
    }

    private function createThread(User ...$participants): Thread
    {
        $thread = Thread::create(['subject' => 'Private']);
        foreach ($participants as $participant) {
            Participant::create(['thread_id' => $thread->id, 'user_id' => $participant->id, 'last_read' => now()]);
        }

        return $thread;
    }
}
