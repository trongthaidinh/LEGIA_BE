<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'owner_id',
        'content',
        'post_image_comment_id',
        'post_video_comment_id',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function postImage()
    {
        return $this->belongsTo(PostImage::class, 'post_image_comment_id');
    }

    public function postVideo()
    {
        return $this->belongsTo(PostVideos::class, 'post_video_comment_id');
    }
}
