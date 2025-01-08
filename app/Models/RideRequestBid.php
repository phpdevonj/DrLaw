<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RideRequestBid extends Model
{
    use HasFactory;

    protected $fillable = ['ride_request_id', 'driver_id', 'bid_amount','is_bid_accept','notes'];

    public function rideRequest()
    {
        return $this->belongsTo(RideRequest::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
