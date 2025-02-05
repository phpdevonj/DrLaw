<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointHistory extends Model
{
    use HasFactory;

    protected $table = 'points_histories';

    protected $fillable = [ 'user_id', 'ride_request_id', 'type', 'transaction_type', 'amount', 'balance', 'description', 'datetime' ];

    protected $casts = [
        'user_id'   => 'integer',
        'ride_request_id' => 'integer',
        'amount'    => 'double',
        'balance'   => 'double',        
    ];
    
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function point_user() {
        return $this->hasOne(Point::class, 'user_id', 'user_id');
    }

    public function scopemyPointHistory($query)
    {
        $user = auth()->user();

        if(\Auth::user()->hasAnyRole(['demo_admin'])){
            $query = $query;
        } else {
            $query = $query->where('user_id', $user->id);
        }

        return  $query;
    }
}
