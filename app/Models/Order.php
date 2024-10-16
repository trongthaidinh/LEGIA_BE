<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'city',
        'district',
        'address',
        'note',
        'payment_method',
        'shipping_fee',
        'subtotal',
        'total',
        'status',
        'order_key'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
