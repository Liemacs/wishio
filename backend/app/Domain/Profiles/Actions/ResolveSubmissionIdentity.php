<?php

namespace App\Domain\Profiles\Actions;

use App\Domain\People\Models\Person;
use App\Domain\Profiles\Models\ProfileSubmission;
use App\Support\Names\NameNormalizer;
use Illuminate\Support\Facades\DB;

/**
 * Răspunsul proprietarului la „cine este?”: un contact al lui, sau cineva nou.
 *
 * Contactul ales primește ziua și interesele ca `subject_provided`, iar
 * legătura rămâne confirmată: următoarele completări ale aceluiași om ajung
 * direct la el.
 *
 * Alte completări nerezolvate ale aceluiași om, pe același link și cu același
 * nume complet, primesc aceeași alegere. Altfel proprietarul ar primi aceeași
 * întrebare de două ori.
 */
class ResolveSubmissionIdentity
{
    public function __construct(
        private readonly AcceptSubmission $accept,
        private readonly NameNormalizer $normalizer,
    ) {}

    public function __invoke(ProfileSubmission $submission, ?Person $person): Person
    {
        return DB::transaction(function () use ($submission, $person) {
            $name = $this->normalizer->normalize($submission->display_name);

            $sameSubmitter = ProfileSubmission::query()
                ->where('public_profile_id', $submission->public_profile_id)
                ->pending()
                ->orderBy('id')
                ->get()
                ->filter(fn (ProfileSubmission $pending) => $pending->is($submission)
                    || ($name !== '' && $this->normalizer->normalize($pending->display_name) === $name));

            foreach ($sameSubmitter as $pending) {
                // La „cineva nou”, prima completare creează persoana; restul o urmează.
                $person = ($this->accept)($pending, $person);

                if (! $person->from_public_link) {
                    $pending->update(['identity_confirmed_at' => now()]);
                }
            }

            return $person;
        });
    }
}
