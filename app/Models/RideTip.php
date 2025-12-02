<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RideTip extends Model
{
    use HasFactory;

    protected $fillable = [
        'ride_request_id',
        'tip_amount',
        'payment_type',
        'status',
        'received_by',
        'payment_intent_id',
    ];

    /**
     * Relationship: Each tip belongs to a ride request
     */
    public function rideRequest()
    {
        return $this->belongsTo(RideRequest::class, 'ride_request_id');
    }
}
