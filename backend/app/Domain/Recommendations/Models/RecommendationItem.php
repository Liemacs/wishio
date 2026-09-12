<?php

namespace App\Domain\Recommendations\Models;

use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecommendationItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'score'           => 'float',
        'score_breakdown' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(RecommendationRun::class, 'recommendation_run_id');
    }
}
