<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id', 'payment_id', 'amount', 'status',
        'method', 'transaction_data', 'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_data' => 'array',
        'paid_at' => 'datetime'
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function isPaid()
    {
        return $this->status === 'completed';
    }
}
