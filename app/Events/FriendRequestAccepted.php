<?php

namespace App\Events;

use App\Models\Friendship;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendRequestAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $friendship;

    public function __construct(Friendship $friendship)
    {
        $this->friendship = $friendship;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('users.' . $this->friendship->owner_id);
    }
}
