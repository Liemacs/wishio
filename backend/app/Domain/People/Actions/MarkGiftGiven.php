<?php

namespace App\Domain\People\Actions;

use App\Domain\People\Models\GiftHistory;
use App\Domain\People\Models\GiftIdea;
use Illuminate\Support\Facades\DB;

/**
 * „Am oferit cadoul”: ideea devine o intrare în istoric.
 *
 * Istoricul e sursa anti-repetării din recomandări (`GenerateRecommendations`
 * exclude produsele de aici), deci din acest moment produsul nu mai e propus
 * aceleiași persoane.
 */
class MarkGiftGiven
{
    public function __invoke(GiftIdea $idea, ?int $year = null, ?string $occasionType = null, ?float $amount = null): GiftHistory
    {
        return DB::transaction(function () use ($idea, $year, $occasionType, $amount) {
            $idea->loadMissing('person.user');

            $entry = GiftHistory::create([
                'user_id'    => $idea->user_id,
                'person_id'  => $idea->person_id,
                'product_id' => $idea->product_id,
                'title'      => $idea->title,
                // Anul din fusul utilizatorului: un cadou oferit la 00:30 pe
                // 1 ianuarie la Chișinău e din anul nou, chiar dacă în UTC nu.
                'year'          => $year ?? now()->setTimezone($idea->person->user->timezone)->year,
                'occasion_type' => $occasionType,
                'amount'        => $amount ?? $idea->price,
            ]);

            $idea->delete();

            return $entry;
        });
    }
}
