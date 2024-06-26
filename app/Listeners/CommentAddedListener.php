<?php

namespace App\Listeners;

use App\Events\CommentAdded;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CommentAddedListener
{
    public function handle(CommentAdded $event)
    {
        $comment = $event->comment;
        $post = $comment->post;
        $postOwner = $post->owner;

        $emitterName = $comment->owner->last_name . ' ' . $comment->owner->first_name;

        Notification::create([
            'owner_id' => $postOwner->id,
            'emitter_id' => $comment->owner->id,
            'type' => 'post_comment',
            'content' => "đã bình luận bài viết của bạn",
            'read' => false,
        ]);
    }
}
