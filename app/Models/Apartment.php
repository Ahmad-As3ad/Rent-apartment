<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    use HasFactory;

   protected $fillable = [
    'owner_id', 'title', 'description', 'address', 'city', 'region',
    'price_per_night', 'number_of_rooms', 'number_of_bathrooms', 'area',
    'is_available', 'approved_by_admin'
];

protected $casts = [
    'is_available' => 'boolean',
    'approved_by_admin' => 'boolean',
    'price_per_night' => 'decimal:2'
];
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function images()
    {
        return $this->hasMany(ApartmentImage::class);
    }

    public function primaryImage()
    {
        return $this->hasOne(ApartmentImage::class)->where('is_primary', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
                     ->where('approved_by_admin', true);
    }


    public function scopeByCity($query, $city)
    {
        if ($city && $city != '') {
            return $query->where('city', 'like', '%' . $city . '%');
        }
        return $query;
    }


    public function scopeByRooms($query, $rooms)
    {
        if ($rooms && $rooms > 0) {
            return $query->where('number_of_rooms', '>=', $rooms);
        }
        return $query;
    }


    public function scopeOrderByHighestPrice($query)
    {
        return $query->orderBy('price_per_night', 'desc');
    }


    public function scopeOrderByLowestPrice($query)
    {
        return $query->orderBy('price_per_night', 'asc');
    }


    public function scopePriceRange($query, $minPrice, $maxPrice)
    {
        if ($minPrice && $minPrice > 0) {
            $query->where('price_per_night', '>=', $minPrice);
        }

        if ($maxPrice && $maxPrice > 0) {
            $query->where('price_per_night', '<=', $maxPrice);
        }

        return $query;
    }

    public function isOwnedBy($userId)
    {
        return $this->owner_id == $userId;
    }


    public function getFullAddressAttribute()
    {
        return "{$this->address}, {$this->city}";
    }


    public function getFormattedPriceAttribute()
    {
        return number_format($this->price_per_night, 2) . 'l.s/per night';
    }
}
