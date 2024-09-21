<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contacts extends Model
{
    use HasFactory;

    protected $table = 'contacts_1';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'title',
        'content',
    ];
}
