<?php

namespace App\Domain\Profiles\Actions;

use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Person;
use App\Domain\Profiles\Models\ProfileSubmission;
use Illuminate\Support\Facades\DB;

/**
 * Retragerea unei completări, din linkul primit de cine a completat.
 *
 * Pagina îi promite omului că proprietarul nu-i mai vede datele (docs/06 § 5).
 * Asta nu înseamnă mereu ștergerea persoanei:
 *
 *  - persoana apărută din link, needitată de proprietar, se șterge cu tot cu
 *    ocaziile ei;
 *  - altfel persoana rămâne, fiindcă un contact care exista înainte nu se șterge
 *    niciodată (docs/00 § D-021), iar editările proprietarului sunt munca lui.
 *    Pierde însă tot ce a trimis omul: ziua, ocazia de naștere, interesele și
 *    proveniența lor. Fără proveniență, următoarea sincronizare a agendei își
 *    poate reface propriile valori.
 *
 * Cât timp o altă completare a aceluiași om folosește persoana, datele rămân:
 * consimțământul pentru ea nu a fost retras.
 */
class WithdrawSubmission
{
    public function __invoke(ProfileSubmission $submission): void
    {
        DB::transaction(function () use ($submission) {
            $person = $submission->person;

            $submission->delete();

            if ($person === null || ProfileSubmission::where('person_id', $person->id)->exists()) {
                return;
            }

            if ($person->from_public_link && $person->fieldSources()->whereNotNull('overridden_at')->doesntExist()) {
                $person->forceDelete();

                return;
            }

            $this->forgetProvidedData($person);
        });
    }

    private function forgetProvidedData(Person $person): void
    {
        $provided = $person->fieldSources()->where('source', FieldSource::SubjectProvided->value)->get();

        if ($provided->contains('field', 'birth_date')) {
            $person->forceFill(['birth_date' => null, 'birth_year_known' => false])->save();

            // Sincronizarea ocaziilor nu șterge o zi de naștere confirmată, iar
            // cele de naștere se creează confirmate. Fără ștergerea explicită,
            // proprietarul ar primi remindere pentru ziua retrasă.
            $person->occasions()
                ->where('type', 'birthday')
                ->where('source', FieldSource::SubjectProvided->value)
                ->delete();
        }

        // Numele nu poate rămâne gol, așa că rămâne valoarea. Pleacă doar
        // proveniența, ca agenda să-l poată rescrie.
        $person->fieldSources()->whereKey($provided->modelKeys())->delete();

        $person->interests()->wherePivot('source', FieldSource::SubjectProvided->value)->detach();
    }
}
