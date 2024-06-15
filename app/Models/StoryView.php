<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryView extends Model
{
    use HasFactory;

    protected $fillable = [
        'story_id',
        'user_id',
    ];

    /**
     * Get the story that owns the view.
     */
    public function story()
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * Get the user who viewed the story.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

