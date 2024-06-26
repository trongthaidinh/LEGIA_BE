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


        Notification::create([
            'owner_id' => $receiver->id,
            'emitter_id' => $sender->id,
            'type' => 'friend_request',
            'content' => "đã gửi cho bạn lời mời kết bạn",
            'read' => false,
        ]);
    }
}
