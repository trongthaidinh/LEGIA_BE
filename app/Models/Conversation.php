<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'name',
        'secret_key'
    ];

    public function creator() {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }
}
