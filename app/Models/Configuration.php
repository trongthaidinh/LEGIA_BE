<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $fillable = [
        'name',
        'homepage_slider',
        'contact_email',
        'phone_number',
    ];

    protected $casts = [
        'homepage_slider' => 'array',
    ];
}
