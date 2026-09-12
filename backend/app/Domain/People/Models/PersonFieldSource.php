<?php

namespace App\Domain\People\Models;

use App\Domain\People\Enums\FieldSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonFieldSource extends Model
{
    protected $guarded = [];

    protected $casts = [
        'source'        => FieldSource::class,
        'confidence'    => 'float',
        'overridden_at' => 'datetime',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
