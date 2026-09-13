<?php

namespace App\Domain\People\Actions;

use App\Domain\Catalog\Models\Product;
use App\Domain\People\Models\GiftIdea;
use App\Domain\People\Models\Person;

/**
 * Salvează o idee de cadou pentru o persoană.
 *
 * Un produs din catalog se salvează o singură dată pentru aceeași persoană: a
 * doua apăsare pe „Salvează” întoarce ideea existentă. Starea poate doar să
 * avanseze — o idee deja cumpărată nu redevine „idee” la o nouă salvare.
 */
class SaveGiftIdea
{
    public function __invoke(Person $person, ?Product $product, ?string $title, string $status = 'idea'): GiftIdea
    {
        if ($product === null) {
            return $person->giftIdeas()->create([
                'user_id' => $person->user_id,
                'title'   => $title,
                'status'  => $status,
            ]);
        }

        $offer = $product->loadMissing('offers')->bestOffer();

        $idea = $person->giftIdeas()->firstOrCreate(
            ['product_id' => $product->id],
            [
                'user_id'  => $person->user_id,
                'title'    => $product->title,
                'price'    => $offer?->price,
                'currency' => $offer?->currency,
                'status'   => $status,
            ],
        );

        if (! $idea->wasRecentlyCreated && $this->rank($status) > $this->rank($idea->status)) {
            $idea->update(['status' => $status]);
        }

        return $idea;
    }

    private function rank(string $status): int
    {
        return (int) array_search($status, GiftIdea::STATUSES, true);
    }
}
