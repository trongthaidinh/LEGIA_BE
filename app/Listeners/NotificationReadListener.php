<?php

namespace App\Listeners;

use App\Events\NotificationRead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotificationReadListener
{
    /**
     * Handle the event.
     *
     * @param NotificationRead $event
     * @return void
     */
    public function handle(NotificationRead $event)
    {
        $notification = $event->notification;
        $notification->read = true;
        $notification->save();
    }
}
