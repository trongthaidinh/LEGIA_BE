<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageImage extends Model
{
    use HasFactory;

    protected $table = 'message_images';

    protected $fillable = [
        'message_id',
        'url',
    ];

}
