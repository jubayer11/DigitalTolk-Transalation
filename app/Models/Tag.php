<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    public function translationKeys()
    {
        return $this->belongsToMany(TranslationKey::class, 'translation_key_tag');
    }

}
