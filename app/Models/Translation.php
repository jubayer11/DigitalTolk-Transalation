<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $fillable = [
        'translation_key_id',
        'locale',
        'content',
    ];
    public function translationKey()
    {
        return $this->belongsTo(TranslationKey::class);
    }

}
