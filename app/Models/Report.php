<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'emitter_id',
        'target_id',
        'type',
        'code',
        'status',
        'description',
    ];

    public function emitter()
    {
        return $this->belongsTo(User::class, 'emitter_id');
    }

    public function target()
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function images()
    {
        return $this->hasMany(ReportImage::class);
    }
}
