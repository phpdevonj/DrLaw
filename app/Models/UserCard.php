<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCard extends Model
{
    use HasFactory;

    protected $table = 'user_cards';

    protected $fillable = [
        'user_id',
        'gateway',
        'vault_id',
        'card_brand',
        'last_digits',
        'expiry',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'user_id' => 'integer'
    ];

    /**
     * Relationship with the User model.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
