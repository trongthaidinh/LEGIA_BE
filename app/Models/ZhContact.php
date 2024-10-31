<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZhContact extends Model
{
    use HasFactory;

    protected $table = 'zh_contacts';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'content',
    ];
}
