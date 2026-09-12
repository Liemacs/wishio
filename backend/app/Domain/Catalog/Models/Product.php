<?php

namespace App\Domain\Catalog\Models;

use App\Domain\People\Models\Interest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'gift_score'      => 'integer',
        'is_giftable'     => 'boolean',
        'score_is_manual' => 'boolean',
    ];

    /** Sub acest prag, produsul nu e sugerat activ. Vezi docs/05 § 4. */
    public const RECOMMENDABLE_SCORE = 3;

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function interests(): BelongsToMany
    {
        return $this->belongsToMany(Interest::class, 'product_interests')->withPivot('weight');
    }

    /** Cea mai ieftină ofertă în stoc — prețul pe care îl arătăm. */
    public function bestOffer(): ?Offer
    {
        return $this->offers
            ->where('in_stock', true)
            ->sortBy('price')
            ->first();
    }

    /**
     * Produsele care pot ajunge în recomandări.
     *
     * Restul catalogului rămâne căutabil, dar nu e sugerat activ: diferența
     * dintre „ți-am găsit 8 idei” și „ți-am aruncat 8 produse”.
     */
    public function scopeRecommendable(Builder $query): void
    {
        $query->where('is_giftable', true)
            ->where('gift_score', '>=', self::RECOMMENDABLE_SCORE)
            ->whereHas('offers', fn (Builder $offers) => $offers->where('in_stock', true));
    }

    public function scopeInBudget(Builder $query, ?int $min, ?int $max): void
    {
        $query->whereHas('offers', function (Builder $offers) use ($min, $max) {
            $offers->where('in_stock', true);

            if ($min !== null) {
                $offers->where('price', '>=', $min);
            }

            if ($max !== null) {
                $offers->where('price', '<=', $max);
            }
        });
    }
}
