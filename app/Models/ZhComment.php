<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZhComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'zh_product_id',
        'name',
        'content',
        'email',
        'images',
        'date',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function zhProduct()
    {
        return $this->belongsTo(ZhProduct::class);
    }
}
