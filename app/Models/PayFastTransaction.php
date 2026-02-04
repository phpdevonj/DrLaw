<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayFastTransaction extends Model
{
    use HasFactory;

    protected $table = 'payfast_transactions';

    protected $fillable = [
        'user_id',
        'm_payment_id',
        'pf_payment_id',
        'amount',
        'status',
        'raw_payload',
        'credited_at'
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'credited_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
