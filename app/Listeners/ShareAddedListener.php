<?php

namespace App\Listeners;

use App\Events\ShareAdded;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ShareAddedListener
{
    /**
     * Handle the event.
     *
     * @param ShareAdded $event
     * @return void
     */
    public function handle(ShareAdded $event)
    {
        $shareAdded = $event->shareAdded;
        $postOwner = $shareAdded->post->owner;

        Notification::create([
            'owner_id' => $postOwner->id,
            'emitter_id' => $shareAdded->owner->id,
            'type' => 'your_post_shared',
            'content' => "đã chia sẻ một bài viết của bạn",
            'read' => false,
        ]);
    }
}

