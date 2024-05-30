<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'emitter_id',
        'type',
        'content',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function emitter()
    {
        return $this->belongsTo(User::class, 'emitter_id');
    }

}
