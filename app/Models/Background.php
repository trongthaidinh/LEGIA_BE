<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Background extends Model
{
    use HasFactory;

    protected $fillable = [
        'value', 'text_color', 'is_hidden'
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
