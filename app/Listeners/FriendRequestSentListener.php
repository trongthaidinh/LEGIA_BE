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
     * @param  FriendRequestSent  $event
     * @return void
     */
    public function handle(FriendRequestSent $event)
    {
        $friendRequest = $event->friendRequest;
        $receiver = $friendRequest->friend;
        $sender = $friendRequest->owner;

        $fullName = $sender->last_name . ' ' . $sender->first_name;

        Notification::create([
            'user_id' => $receiver->id,
            'type' => 'friend_request_sent',
            'data' => json_encode([
                'message' => "{$fullName} đã gửi cho bạn một lời mời kết bạn",
                'friend_request_id' => $friendRequest->id,
            ],JSON_UNESCAPED_UNICODE),
        ]);
    }
}
