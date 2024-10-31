<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZhConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'homepage_slider',
        'contact_email',
        'phone_number'
    ];

    protected $casts = [
        'homepage_slider' => 'array'
    ];
}
