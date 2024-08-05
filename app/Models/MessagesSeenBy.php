<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessagesSeenBy extends Model
{
    use HasFactory;

    protected $table = 'messages_seen_by';

    protected $fillable = [
        'user_id',
        'message_id',
    ];
}
