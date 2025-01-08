<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LanguageWithKeyword extends Model
{
    use HasFactory;

    protected $fillable = [ 'language_id', 'keyword_id','screen_id','keyword_value'];

    protected $casts = [
        'language_id'      => 'integer',
        'keyword_id'      => 'integer',
    ];

    public function languagelist()
    {
        return $this->belongsTo(LanguageList::class, 'language_id','id');
    }

    public function defaultkeyword()
    {
        return $this->belongsTo(DefaultKeyword::class, 'keyword_id','keyword_id');
    }

    public function screen()
    {
        return $this->belongsTo(Screen::class, 'screen_id', 'screenId');
    }
}
