<?php

namespace App\Domain\Account\Actions;

use App\Models\User;

/**
 * Tot ce deținem despre un utilizator, într-un singur obiect.
 *
 * Dreptul la portabilitate din Legea 195/2024 (și GDPR): datele trebuie să
 * poată fi obținute într-un format structurat, uzual și citibil automat.
 *
 * Notele despre persoane SE INCLUD: sunt datele lui, chiar dacă le ținem
 * criptate. Hash-urile și identificatorii interni NU se includ — nu-i spun
 * nimic și nu sunt datele lui.
 */
class ExportUserData
{
    public function __invoke(User $user): array
    {
        $user->load([
            'people.interests', 'people.occasions.nameDay', 'people.occasions.holiday',
            'people.giftHistory', 'people.giftIdeas', 'people.fieldSources',
            'settings', 'publicProfile.submissions', 'wishlistItems',
        ]);

        return [
            'exported_at' => now()->toIso8601String(),
            'format'      => 'wishio/export/1',

            'account' => [
                'name'              => $user->name,
                'email'             => $user->email,
                'locale'            => $user->locale,
                'country_code'      => $user->country_code,
                'timezone'          => $user->timezone,
                'name_day_calendar' => $user->name_day_calendar,
                'birth_date'        => $user->birth_date?->toDateString(),
                'ai_consent'        => $user->ai_consent_at !== null,
                'registered_at'     => $user->created_at?->toIso8601String(),
            ],

            'settings' => $user->settings ? [
                'reminder_days'  => $user->settings->reminderDays(),
                'preferred_hour' => $user->settings->preferred_hour,
                'quiet_hours'    => [$user->settings->quiet_from, $user->settings->quiet_to],
                'push_enabled'   => $user->settings->push_enabled,
                'email_digest'   => $user->settings->email_digest,
            ] : null,

            'people' => $user->people->map(fn ($person) => [
                'name'             => $person->display_name,
                'relationship'     => $person->relationship,
                'gender'           => $person->gender,
                'birth_date'       => $person->birth_date?->toDateString(),
                'birth_year_known' => $person->birth_year_known,
                'budget'           => [$person->budget_min, $person->budget_max],
                'notes'            => $person->notes,
                'interests'        => $person->interests->map(fn ($i) => [
                    'code'       => $i->code,
                    'source'     => $i->pivot->source,
                    'confidence' => (float) $i->pivot->confidence,
                ])->values(),
                'occasions' => $person->occasions->map(fn ($o) => [
                    'type'      => $o->type,
                    'date'      => sprintf('%02d-%02d', $o->month, $o->day),
                    'year'      => $o->year,
                    'saint'     => $o->nameDay?->saintName($user->locale),
                    'confirmed' => $o->confirmed_at !== null,
                    'source'    => $o->source->value,
                ])->values(),
                'gift_history' => $person->giftHistory->map(fn ($g) => [
                    'title' => $g->title, 'year' => $g->year, 'amount' => $g->amount,
                ])->values(),
                'gift_ideas' => $person->giftIdeas->map(fn ($i) => [
                    'title' => $i->title, 'status' => $i->status, 'price' => $i->price, 'currency' => $i->currency,
                ])->values(),
                // Proveniența fiecărui câmp: utilizatorul are dreptul să știe
                // de unde avem ce avem despre oamenii din agenda lui.
                'field_sources' => $person->fieldSources->mapWithKeys(fn ($s) => [
                    $s->field => ['source' => $s->source->value, 'confidence' => (float) $s->confidence],
                ]),
                'added_at' => $person->created_at?->toIso8601String(),
            ])->values(),

            'wishlist' => $user->wishlistItems->map(fn ($item) => [
                'kind'       => $item->kind, 'title' => $item->title,
                'visibility' => $item->visibility, 'note' => $item->note,
            ])->values(),

            'public_profile' => $user->publicProfile ? [
                'url'         => url('/@'.$user->publicProfile->slug),
                'visibility'  => $user->publicProfile->visibility,
                'views'       => $user->publicProfile->view_count,
                'submissions' => $user->publicProfile->submissions->map(fn ($s) => [
                    'name'         => $s->display_name,
                    'birth_date'   => $s->birth_date?->toDateString(),
                    'message'      => $s->message,
                    'submitted_at' => $s->created_at?->toIso8601String(),
                ])->values(),
            ] : null,
        ];
    }
}
