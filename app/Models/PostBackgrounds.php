<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostBackground extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'background_id',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function background()
    {
        return $this->belongsTo(Background::class);
    }
}
