<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurgePrice extends Model
{
    use HasFactory , SoftDeletes;
    protected $fillable = [ 'day', 'type', 'value', 'from_time', 'to_time' ];

    protected $casts = [
        'day' => 'array',
        'from_time' => 'array',
        'to_time' => 'array',
    ];

    public function getDayAttribute($value)
    {
        return isset($value) ? json_decode($value, true) : null; 
    }

    public function setDayAttribute($value)
    {
        $this->attributes['day'] = isset($value) ? json_encode($value) : null;
    }

    public function getFromTimeAttribute($value)
    {
        return isset($value) ? json_decode($value, true) : null; 
    }

    public function setFromTimeAttribute($value)
    {
        $this->attributes['from_time'] = isset($value) ? json_encode($value) : null;
    }

    public function getToTimeAttribute($value)
    {
        return isset($value) ? json_decode($value, true) : null; 
    }

    public function setToTimeAttribute($value)
    {
        $this->attributes['to_time'] = isset($value) ? json_encode($value) : null;
    }
}
