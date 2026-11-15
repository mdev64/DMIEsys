<?php

namespace App\Http\Livewire;

use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\View\View;
use Livewire\Component;

class DashboardNotifications extends Component
{
    /**
     * Only the latest notifications are rendered, so only those are loaded.
     */
    private const VISIBLE_NOTIFICATIONS = 10;

    /** @var bool */
    public $hasNotifications = false;

    /** @var bool */
    public $hasUnreadNotifications = false;

    /** @var int */
    public $unread_notifications_count = 0;

    /** @var DatabaseNotificationCollection|null */
    public $all_notifications = null;

    /** @var int */
    public $all_notifications_count = 0;

    /** @var int */
    public $notificationCount = 0;

    /** @var array */
    protected $listeners = ['updateNotificationBadge' => 'loadNotifications'];

    public function mount(): void
    {
        $this->loadNotifications();
    }

    /**
     * Refresh the notification list and counters. This must never dispatch
     * "updateNotificationBadge" itself, because it is the listener of that event.
     */
    public function loadNotifications(): void
    {
        $user = auth()->user();

        $this->all_notifications = $user->notifications()->limit(self::VISIBLE_NOTIFICATIONS)->get();
        $this->all_notifications_count = $user->notifications()->count();
        $this->unread_notifications_count = $user->unreadNotifications()->count();
        $this->notificationCount = $this->unread_notifications_count;

        $this->hasUnreadNotifications = $this->unread_notifications_count > 0;
        $this->hasNotifications = $this->all_notifications_count > 0;
    }

    public function render(): View
    {
        return view('livewire.dashboard-notifications');
    }

    public function MarkAsRead(string $id): void
    {
        auth()->user()->unreadNotifications()->where('id', $id)->update(['read_at' => now()]);

        $this->refreshNotifications();
    }

    public function Delete(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->delete();

        $this->refreshNotifications();
    }

    public function ReadAll(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        $this->refreshNotifications();
    }

    public function DeleteAll(): void
    {
        auth()->user()->notifications()->delete();

        $this->refreshNotifications();
    }

    private function refreshNotifications(): void
    {
        $this->loadNotifications();

        $this->dispatch('updateNotificationBadge')->to(NotificationBadge::class);
    }
}
