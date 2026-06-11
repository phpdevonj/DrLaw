<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileMoneyRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reference_id',
        'provider_transaction_id',
        'amount',
        'currency',
        'phone_number',
        'type',
        'status',
        'response_payload',
        'response_message',
    ];

    protected $casts = [
        'response_payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
