<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayHubTransaction extends Model
{
    use HasFactory;

    protected $table = 'payhub_transactions';

    protected $fillable = [
        'user_id',
        'reference_id',
        'transaction_id',
        'epay_transaction_id',
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
