<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'customer_fee',
        'courier_fee',
        'is_active',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}