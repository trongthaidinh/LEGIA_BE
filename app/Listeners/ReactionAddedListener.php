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
        $postOwner = $reaction->post->owner;

        $fullName = $postOwner->last_name . ' ' . $postOwner->first_name;

        Notification::create([
            'user_id' => $postOwner->id,
            'type' => 'reaction_added',
            'data' => json_encode([
                'message' => "{$fullName} đã bày tỏ cảm xúc bài viết của bạn",
                'reaction_type' => $reaction->type,
                'post_id' => $reaction->post_id,
            ],JSON_UNESCAPED_UNICODE),
        ]);
    }
}
