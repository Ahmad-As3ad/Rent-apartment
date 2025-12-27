<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'phone_number',
        'phone_verified_at',
        'first_name',
        'last_name',
        'profile_picture',
        'date_of_birth',
        'id_card_picture',
        'user_type',
        'status',
        'profile_completed_at',
        'password',
        'reviewed_at',
        'admin_notes',
        'reviewed_by'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'profile_completed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];


    public function isProfileComplete(): bool
    {
        return $this->profile_completed_at !== null &&
            !empty($this->first_name) &&
            !empty($this->last_name) &&
            !empty($this->date_of_birth);
    }


    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }


    public function isOwner(): bool
    {
        return $this->user_type === 'owner';
    }

    public function isTenant(): bool
    {
        return $this->user_type === 'tenant';
    }


    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }


    public function getInitialsAttribute(): string
    {
        $first = mb_substr($this->first_name, 0, 1, 'UTF-8');
        $last = mb_substr($this->last_name, 0, 1, 'UTF-8');
        return strtoupper($first . $last);
    }


    public function getProfilePictureUrlAttribute(): ?string
    {
        if ($this->profile_picture) {
            return asset('storage/' . $this->profile_picture);
        }
        return null;
    }

    public function getIdCardPictureUrlAttribute(): ?string
    {
        if ($this->id_card_picture) {
            return asset('storage/' . $this->id_card_picture);
        }
        return null;
    }


    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class, 'owner_id');
    }


    public function scopeOwners($query)
    {
        return $query->where('user_type', 'owner');
    }


    public function scopeTenants($query)
    {
        return $query->where('user_type', 'tenant');
    }


    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }


    public function scopeProfileComplete($query)
    {
        return $query->whereNotNull('profile_completed_at')
            ->whereNotNull('first_name')
            ->whereNotNull('last_name')
            ->whereNotNull('date_of_birth');
    }


    public function canPerformAction(): bool
    {
        return $this->isApproved() && $this->isProfileComplete();
    }


    public function getApartmentsCountAttribute(): int
    {
        return $this->apartments()->count();
    }


    public function getActiveBookingsCountAttribute(): int
    {
        return $this->bookings()->active()->count();
    }


    public function getMaskedPhoneAttribute(): string
    {
        $phone = $this->phone_number;
        if (strlen($phone) > 4) {
            $masked = substr($phone, 0, 2) . str_repeat('*', strlen($phone) - 4) . substr($phone, -2);
            return $masked;
        }
        return $phone;
    }


    public function getAgeAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }

        return $this->date_of_birth->age;
    }

    public function getProfileCompletionTimeAttribute(): ?string
    {
        if (!$this->profile_completed_at) {
            return null;
        }

        return $this->created_at->diffForHumans($this->profile_completed_at);
    }

}
