<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    use HasFactory;

    protected $fillable = [ 'user_id', 'total_points' ];

    protected $casts = [
        'user_id'           => 'integer',
        'total_points'      => 'double',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
