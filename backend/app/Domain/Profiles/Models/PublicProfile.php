<?php

namespace App\Domain\Profiles\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PublicProfile extends Model
{
    protected $guarded = [];

    protected $casts = [
        'visibility'   => 'array',
        'is_active'    => 'boolean',
        'is_indexable' => 'boolean',
    ];

    /**
     * Implicit, aproape nimic nu e public.
     *
     * Pagina publică e o invitație de a-ți spune datele TALE, nu o vitrină cu
     * ale mele. Fiecare câmp expus e o decizie conștientă a utilizatorului.
     */
    public const DEFAULT_VISIBILITY = [
        'birth_date' => 'private',
        'interests'  => 'private',
        'wishlist'   => 'private',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ProfileSubmission::class);
    }

    public function shows(string $field): bool
    {
        return ($this->visibility[$field] ?? self::DEFAULT_VISIBILITY[$field] ?? 'private') === 'public';
    }

    /**
     * Slug neghicibil: nume lizibil + sufix aleator.
     *
     * Fără sufix, „@ion” ar permite oricui să enumereze profilurile
     * utilizatorilor, iar pagina ar deveni un director de persoane.
     */
    public static function generateSlug(string $displayName): string
    {
        $base = Str::slug(Str::limit($displayName, 24, ''));
        $base = $base !== '' ? $base : 'wishio';

        do {
            $slug = $base.'-'.Str::lower(Str::random(6));
        } while (self::where('slug', $slug)->exists());

        return $slug;
    }
}
