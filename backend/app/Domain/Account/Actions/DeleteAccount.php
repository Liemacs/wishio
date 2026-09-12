<?php

namespace App\Domain\Account\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Ștergerea completă a contului.
 *
 * Apple cere ca ștergerea să fie posibilă DIN aplicație, nu printr-un email
 * (docs/06 § 2). Legea 195/2024 cere ca ea să fie reală, nu o dezactivare.
 *
 * Ștergem definitiv, nu soft-delete: un cont „șters” care rămâne în baza de
 * date nu e șters, e ascuns.
 */
class DeleteAccount
{
    public function __invoke(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Persoanele au soft-delete; la ștergerea contului dispar de tot,
            // împreună cu notele, interesele, ocaziile și istoricul.
            $user->people()->withTrashed()->forceDelete();

            // Restul cade prin cascadă: setări, dispozitive, notificări,
            // profil public, submisii, liste de dorințe, rulări, clickuri.
            $user->tokens()->delete();
            $user->forceDelete();
        });
    }
}
