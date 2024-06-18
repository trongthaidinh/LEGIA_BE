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

        $fullName = $sender->last_name . ' ' . $sender->first_name;

        Notification::create([
            'user_id' => $receiver->id,
            'type' => 'friend_request_accepted',
            'data' => json_encode([
                'message' => "{$fullName} đã chấp nhận lời mời kết bạn của bạn",
                'friendship_id' => $friendship->id,
            ],JSON_UNESCAPED_UNICODE),
        ]);
    }
}
