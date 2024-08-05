<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessagesDeletedBy extends Model
{
    use HasFactory;

    protected $table = 'messages_deleted_by';

    protected $fillable = [
        'user_id',
        'message_id',
        'type'
    ];
}
