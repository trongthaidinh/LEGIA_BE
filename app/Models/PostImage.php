<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'post_id',
        'url',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_image_comment_id');
    }
}
