<?php

namespace App\Traits;

use App\Models\User;
use App\Notifications\GeneralNotification;
use App\Notifications\GeneralNotificationJust;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

trait Notify
{
    public function notifyHOD(string $message): void
    {
        Notification::send($this->usersWithAnyRole(['Head of the Department']), new GeneralNotificationJust($message));
    }

    public function notifyAll(string $message): void
    {
        Notification::send(User::all(), new GeneralNotification($message));
    }

    public function notifyTechnicalStaff(string $message): void
    {
        Notification::send($this->usersWithAnyRole(['Technical Officer', 'Instrument Mechanic', 'Machine Operator']), new GeneralNotification($message));
    }

    public function notifyOffice(string $message): void
    {
        Notification::send($this->usersWithAnyRole(['Management Assistant (DMIE)']), new GeneralNotificationJust($message));
    }

    public function notifyAcademicStaff(string $message): void
    {
        Notification::send($this->usersWithAnyRole(['Head of the Department', 'Professor', 'Senior Lecturer', 'Lecturer', 'Contract Basis Lecturer', 'Probationary Lecturer', 'Visiting Lecture', 'Temporary Lecturer', 'Temporary Instructor', 'Instructor']), new GeneralNotification($message));
    }

    public function notifyLecturers(string $message): void
    {
        Notification::send($this->usersWithAnyRole(['Head of the Department', 'Professor', 'Senior Lecturer', 'Lecturer', 'Contract Basis Lecturer', 'Probationary Lecturer', 'Visiting Lecture', 'Temporary Lecturer']), new GeneralNotification($message));
    }

    public function notifySelf(string $message): void
    {
        Notification::send(auth()->user(), new GeneralNotificationJust($message));
    }

    public function notifyUser(object $user, string $message): void
    {
        Notification::send($user, new GeneralNotificationJust($message));
    }

    public function notifyUsers(object $users, string $message, bool $otherCondition = true, int $exceptUserId = 10000): void
    {
        foreach ($users as $user) {
            if ($user->id != $exceptUserId && $otherCondition) {
                Notification::send($user, new GeneralNotification($message));
            }
        }
    }

    /**
     * Fetch only the users holding one of the given roles in a single query,
     * instead of loading every user and checking roles one by one.
     *
     * @param  string[]  $roles
     * @return Collection<int, User>
     */
    private function usersWithAnyRole(array $roles): Collection
    {
        return User::query()
            ->whereHas('roles', function ($query) use ($roles) {
                $query->whereIn('name', $roles);
            })
            ->get();
    }
}
