<?php

namespace App\Http\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class InstantNotification extends Component
{
    /** @var array */
    protected $listeners = ['showInstantNotification' => 'render'];

    /** @var bool */
    public $has_notification = false;

    /** @var string */
    public $notification = '';

    /** @var string */
    public $time = '';

    /**
     * @return View
     */
    public function render()
    {
        $recent_notification = auth()->user()->unreadNotifications()
            ->where('created_at', '>=', now()->subSeconds(11))->first();
        if ($recent_notification) {
            $this->has_notification = true;
            $this->notification = $recent_notification->data['text'];
            $this->time = $recent_notification->created_at->diffForHumans();
        } else {
            $this->has_notification = false;
            $this->notification = '';
            $this->time = '';
        }

        return view('livewire.instant-notification', [
            'has_notification' => $this->has_notification,
            'time' => $this->time,
            'notification' => $this->notification,
        ]);
    }
}
