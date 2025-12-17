<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'title',
        'description',
        'address',
        'city',
        'region',
        'latitude',
        'longitude',
        'price_per_night',
        'number_of_rooms',
        'number_of_bathrooms',
        'has_kitchen',
        'has_air_conditioning',
        'has_wifi',
        'has_parking',
        'has_washer',
        'has_tv',
        'max_guests',
        'area',
        'is_available',
        'approved_by_admin'
    ];

    protected $casts = [
        'has_kitchen' => 'boolean',
        'has_air_conditioning' => 'boolean',
        'has_wifi' => 'boolean',
        'has_parking' => 'boolean',
        'has_washer' => 'boolean',
        'has_tv' => 'boolean',
        'is_available' => 'boolean',
        'approved_by_admin' => 'boolean',
        'price_per_night' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8'
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
        if ($city) {
            return $query->where('city', $city);
        }
        return $query;
    }

    public function scopeByRegion($query, $region)
    {
        if ($region) {
            return $query->where('region', $region);
        }
        return $query;
    }

    public function scopePriceRange($query, $minPrice, $maxPrice)
    {
        if ($minPrice && $maxPrice) {
            return $query->whereBetween('price_per_night', [$minPrice, $maxPrice]);
        } elseif ($minPrice) {
            return $query->where('price_per_night', '>=', $minPrice);
        } elseif ($maxPrice) {
            return $query->where('price_per_night', '<=', $maxPrice);
        }
        return $query;
    }


    public function scopeByRooms($query, $rooms)
    {
        if ($rooms) {
            return $query->where('number_of_rooms', '>=', $rooms);
        }
        return $query;
    }


    public function scopeWithAmenities($query, $amenities)
    {
        if (!empty($amenities)) {
            foreach ($amenities as $amenity) {
                if (in_array($amenity, ['kitchen', 'air_conditioning', 'wifi', 'parking', 'washer', 'tv'])) {
                    $column = 'has_' . $amenity;
                    $query->where($column, true);
                }
            }
        }
        return $query;
    }


    public function isOwnedBy($userId)
    {
        return $this->owner_id == $userId;
    }


    public function getFullAddressAttribute()
    {
        return "{$this->address}, {$this->city}, {$this->region}";
    }

    public function getFormattedPriceAttribute()
    {
        return number_format($this->price_per_night, 2) . 'L.S/night';
    }
}
