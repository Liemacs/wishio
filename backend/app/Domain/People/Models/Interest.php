<?php

namespace App\Domain\People\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'translations'  => 'array',
        'keywords'      => 'array',
        'is_experience' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(InterestGroup::class, 'interest_group_id');
    }

    public function label(?string $locale = null): string
    {
        return self::pickLocale($this->translations, $locale);
    }

    /** @param array<string,string> $translations */
    public static function pickLocale(array $translations, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return $translations[$locale]
            ?? $translations[config('wishio.locales.fallback')]
            ?? (string) reset($translations);
    }

    /**
     * Codurile valide — contractul pe care AI-ul nu are voie sa-l depaseasca.
     *
     * @return list<string>
     */
    public static function validCodes(): array
    {
        return cache()->remember(
            'interests.codes',
            now()->addHour(),
            fn () => self::query()->orderBy('code')->pluck('code')->all()
        );
    }
}
