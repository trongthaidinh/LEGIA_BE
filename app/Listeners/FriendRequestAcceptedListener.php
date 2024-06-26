<?php

namespace App\Listeners;

use App\Events\FriendRequestAccepted;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class FriendRequestAcceptedListener
{
    /**
     * Handle the event.
     *
     * @param FriendRequestAccepted $event
     * @return void
     */
    public function handle(FriendRequestAccepted $event)
    {
        $friendship = $event->friendship;
        $sender = $friendship->friend;
        $receiver = $friendship->owner;

        $senderName = $sender->last_name . ' ' . $sender->first_name;

        Notification::create([
            'owner_id' => $receiver->id,
            'emitter_id' => $sender->id,
            'type' => 'friend_request_accept',
            'content' => "đã chấp nhận lời mời kết bạn của bạn",
            'read' => false,
        ]);
    }
}

