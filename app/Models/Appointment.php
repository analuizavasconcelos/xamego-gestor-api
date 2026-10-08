<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_name', 'description', 'address', 'date', 'time', 'status',
        'total_value', 'amount_paid', 'user_id',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'total_value' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}