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

        $emitterName = $shareAdded->owner->last_name . ' ' . $shareAdded->owner->first_name;

        Notification::create([
            'owner_id' => $postOwner->id,
            'emitter_id' => $shareAdded->owner->id,
            'type' => 'your_post_shared',
            'content' => "{$emitterName} đã chia sẻ một bài viết của bạn",
            'read' => false,
        ]);
    }
}

