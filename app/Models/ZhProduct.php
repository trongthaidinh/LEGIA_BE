<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZhProduct extends Model
{
    use HasFactory;

    protected $table = 'zh_products';

    protected $fillable = [
        'name',
        'price',
        'original_price',
        'features',
        'images',
        'zh_child_nav_id',
        'slug',
        'content',
        'phone_number',
        'available_stock',
    ];

    protected $casts = [
        'features' => 'array',
        'images' => 'array',
    ];

    public function zhParentNav()
    {
        return $this->belongsTo(ZhParentNav::class);
    }

    public function zhChildNav()
    {
        return $this->belongsTo(ZhChildNav::class);
    }

    public function zhComments()
    {
        return $this->hasMany(ZhComment::class);
    }

    public function reduceStock($quantity)
    {
        if ($this->available_stock < $quantity) {
            throw new \Exception('Not enough stock available');
        }
        $this->decrement('available_stock', $quantity);
    }
}
