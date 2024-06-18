<?php

namespace App\Events;

use App\Models\Reaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReactionAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reaction;

    public function __construct(Reaction $reaction)
    {
        $this->reaction = $reaction;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('posts.' . $this->reaction->post_id);
    }

    public function broadcastWith()
    {
        return [
            'reaction' => $this->reaction->load('owner_id'),
        ];
    }
}
