<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;

    protected $table = 'otp_verifications';

    protected $fillable = [
        'email',
        'contact_number',
        'otp',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];
}
