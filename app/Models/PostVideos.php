<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostVideos extends Model
{
    use HasFactory;

    protected $table = 'post_videos';

    protected $fillable = [
        'post_id',
        'user_id',
        'url',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_video_comment_id');
    }
}
