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

        $fullName = $postOwner->last_name . ' ' . $postOwner->first_name;

        Notification::create([
            'user_id' => $postOwner->id,
            'type' => 'post_shared',
            'data' => json_encode([
                'message' => "{$fullName} đã chia sẻ một bài viết của bạn",
                'post_id' => $shareAdded->post_id,
                'share_id' => $shareAdded->id,
            ],JSON_UNESCAPED_UNICODE),
        ]);
    }
}
