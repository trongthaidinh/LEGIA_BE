<?php

namespace App\Events;

use App\Models\Share;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShareAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $shareAdded;

    public function __construct(Share $shareAdded)
    {
        $this->shareAdded = $shareAdded;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('posts.' . $this->shareAdded->post_id);
    }

    public function broadcastWith()
    {
        return [
            'shareAdded' => $this->shareAdded->load('user'),
        ];
    }
}
