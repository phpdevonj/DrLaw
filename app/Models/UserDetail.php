<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    use HasFactory;
    protected $fillable = [ 'user_id', 'car_model', 'car_color', 'car_plate_number', 'car_production_year', 'work_address', 'home_address', 'work_latitude', 'work_longitude', 'home_latitude', 'home_longitude', 'insurance_expiration_date', 'registration_expiration_date' ];

    protected $casts = [
        'user_id' => 'integer',
    ];
}
