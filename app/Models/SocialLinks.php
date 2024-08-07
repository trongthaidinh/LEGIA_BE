<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialLinks extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'telegram_link',
        'facebook_link',
        'instagram_link',
        'x_link'
    ];
}
