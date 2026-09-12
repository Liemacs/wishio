<?php

namespace App\Domain\Catalog\Models;

use App\Domain\People\Models\Interest;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $guarded = [];

    protected $casts = ['translations' => 'array'];

    public function label(?string $locale = null): string
    {
        return Interest::pickLocale($this->translations, $locale);
    }
}
