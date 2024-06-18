<?php

namespace App\Events;

use App\Models\Friendship;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendRequestSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $friendRequest;

    public function __construct(Friendship $friendRequest)
    {
        $this->friendRequest = $friendRequest;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('users.' . $this->friendRequest->friend_id);
    }

    public function broadcastWith()
    {
        return [
            'friendRequest' => $this->friendRequest->load('friend'),
        ];
    }
}
