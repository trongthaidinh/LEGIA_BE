<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Friendship extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'friend_id',
        'status',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function friend()
    {
        return $this->belongsTo(User::class, 'friend_id');
    }


    public function partner()
    {
        $currentUserId = auth()->user()->id ?? null;

        if ($this->owner_id == $currentUserId) {
            return $this->belongsTo(User::class, 'friend_id');
        } else {
            return $this->belongsTo(User::class, 'owner_id');
        }

    }
}
