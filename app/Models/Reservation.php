<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = ['apartment_id', 'tenant_id', 'start_date', 'end_date',
    'status', 'total_price', 'approved_at', 'canceled_at', 'rejected_at', 'notes',
    'new_start_date','new_end_date','modified_requested_at','approved_revalidated_at'];


    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }
    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
    public function scopeOverlap($query, $apartmentId, $start, $end)
    {
        return $query->where('apartment_id', $apartmentId)->where('status', 'approved')
        ->where(function ($q) use ($start, $end) {
            $q->where('start_date', '<', $end)->where('end_date', '>', $start);
        });
    }
    public function payment()
{
    return $this->hasOne(Payment::class);
}

public function isPaid()
{
    return $this->payment && $this->payment->isPaid();
}

public function getTotalPriceAttribute()
{
    if (!$this->start_date || !$this->end_date) {
        return 0;
    }

    $days = $this->start_date->diffInDays($this->end_date);
    return $days * $this->apartment->price_per_night;
}
}
