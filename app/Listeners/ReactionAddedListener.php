<?php

namespace App\Listeners;

use App\Events\ReactionAdded;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ReactionAddedListener
{
    public function handle(ReactionAdded $event)
    {
        $reaction = $event->reaction;
        $post = $reaction->post;
        $postOwner = $post->owner;

        Notification::create([
            'owner_id' => $postOwner->id,
            'emitter_id' => $reaction->owner->id,
            'type' => 'post_like',
            'content' => "đã bày tỏ cảm xúc bài viết của bạn",
            'read' => false,
        ]);
    }
}
