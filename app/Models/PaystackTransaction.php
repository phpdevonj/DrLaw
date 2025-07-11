<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaystackTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'reference_id', 'email', 'amount', 'status', 'response_data', 'last_checked_at'
    ];

    protected $casts = [
        'response_data' => 'array',
    ];
}
