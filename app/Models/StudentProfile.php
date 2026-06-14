<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'avatar',
        'batch',
        'course',
        'profile_completed',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
        

}
