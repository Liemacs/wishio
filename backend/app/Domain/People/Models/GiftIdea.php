<?php

namespace App\Domain\People\Models;

use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * O idee de cadou pentru o persoană: din catalog sau scrisă de mână.
 *
 * Trece prin „aleasă” și „cumpărată”. Când proprietarul spune că a oferit-o,
 * devine o intrare în `gift_history` și dispare de aici (`MarkGiftGiven`).
 */
class GiftIdea extends Model
{
    public const STATUSES = ['idea', 'chosen', 'purchased'];

    protected $guarded = [];

    protected $casts = ['price' => 'decimal:2'];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
