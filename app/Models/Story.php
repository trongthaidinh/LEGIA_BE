<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Story extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'content_url',
        'content_text',
        'background_id',
        'expires_at',
    ];

    protected $dates = ['expires_at'];

    /**
     * Get the user that owns the story.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the background associated with the story.
     */
    public function background()
    {
        return $this->belongsTo(Background::class);
    }

    /**
     * Get the views for the story.
     */
    public function views()
    {
        return $this->hasMany(StoryView::class);
    }
}

