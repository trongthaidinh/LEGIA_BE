<?php

namespace App\Listeners;

use App\Events\FriendRequestSent;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class FriendRequestSentListener
{
    /**
     * Handle the event.
     *
     * @param FriendRequestSent $event
     * @return void
     */
    public function handle(FriendRequestSent $event)
    {
        $friendRequest = $event->friendRequest;
        $receiver = $friendRequest->friend;
        $sender = $friendRequest->owner; 

        $senderName = $sender->last_name . ' ' . $sender->first_name;

        Notification::create([
            'owner_id' => $receiver->id,
            'emitter_id' => $sender->id,
            'type' => 'friend_request',
            'content' => "{$senderName} đã gửi cho bạn một lời mời kết bạn",
            'read' => false,
        ]);
    }
}
