<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
    'name', 'size', 'image_path', 'current_cost', 'current_price',
    'current_stock', 'low_stock_threshold', 'is_active',
    ];

    protected $casts = [
        'current_cost' => 'decimal:2',
        'current_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function priceHistory()
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    public function stockEntries()
    {
        return $this->hasMany(StockEntry::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}