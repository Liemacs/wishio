<?php

namespace App\Domain\Recommendations\Models;

use App\Domain\People\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecommendationRun extends Model
{
    protected $guarded = [];

    protected $casts = [
        'criteria' => 'array',
        'cost'     => 'decimal:5',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecommendationItem::class)->orderBy('rank');
    }
}
