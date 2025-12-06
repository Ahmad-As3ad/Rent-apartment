<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
        'profile_completed_at'
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'profile_completed_at' => 'datetime',
    ];

    public function isProfileComplete()
    {
        return $this->profile_completed_at !== null;
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isOwner()
    {
        return $this->user_type === 'owner';
    }

    public function isTenant()
    {
        return $this->user_type === 'tenant';
    }
}
