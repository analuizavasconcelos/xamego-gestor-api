<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'delivery_address',
        'delivery_type',
        'delivery_zone_id',
        'delivery_fee',
        'courier_fee',
        'courier_name',
        'courier_settled',
        'payment_method',
        'change_for',
        'total_price',
        'total_profit',
        'status',
        'delivered_at',
        'user_id',
    ];

    protected $casts = [
        'delivery_fee'    => 'decimal:2',
        'courier_fee'     => 'decimal:2',
        'change_for'      => 'decimal:2',
        'total_price'     => 'decimal:2',
        'total_profit'    => 'decimal:2',
        'courier_settled' => 'boolean',
        'delivered_at'    => 'datetime',
    ];

    public function deliveryZone()
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}