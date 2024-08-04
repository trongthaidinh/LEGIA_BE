<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersSearchRecent extends Model
{
    use HasFactory;

    protected $table = 'users_search_recent';

    protected $fillable = [
        'user_id',
        'ref_id',
    ];

    public function ref()
    {
        return $this->belongsTo(User::class, 'ref_id');
    }


}
