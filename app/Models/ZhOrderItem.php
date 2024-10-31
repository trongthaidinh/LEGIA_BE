<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZhOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'name',
        'quantity',
        'price',
        'image',
    ];

    public function order()
    {
        return $this->belongsTo(ZhOrder::class);
    }
}
