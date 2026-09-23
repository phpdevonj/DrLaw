<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

class Region extends Model
{
    use HasFactory, HasSpatial;
    
    protected $fillable = [ 'name', 'distance_unit', 'status', 'timezone' ];

    protected $casts = [
        'status' => 'integer',
        'coordinates' => Polygon::class,
    ];

    public function regionSos(){
        return $this->hasMany(regionSos::class, 'region_id', 'id');
    }

}
