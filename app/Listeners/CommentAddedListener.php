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
        $postOwner = $comment->post->owner;

        $fullName = $postOwner->last_name . ' ' . $postOwner->first_name;

        Notification::create([
            'user_id' => $postOwner->id,
            'type' => 'comment_added',
            'data' => json_encode([
                'message' => "{$fullName} đã bình luận bài viết của bạn",
                'comment_id' => $comment->id,
                'post_id' => $comment->post_id,
            ]),
        ]);
    }
}
