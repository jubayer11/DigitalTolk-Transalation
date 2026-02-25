<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    public function translationKey()
    {
        return $this->belongsTo(TranslationKey::class);
    }

}
