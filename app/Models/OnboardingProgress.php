<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingProgress extends Model
{
    protected $fillable = [
        'user_id',
        'current_step',
        'registration_completed',
        'otp_verified',
        'avatar_uploaded',
        'profile_completed',
        'explore_completed',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
