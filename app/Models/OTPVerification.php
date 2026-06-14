<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OTPVerification extends Model
{
    protected $fillable = [
        'user_id',
        'otp_code',
        'expires_at',
        'verified_at',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
