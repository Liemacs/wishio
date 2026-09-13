<?php

namespace App\Domain\Account\Actions;

use App\Domain\Catalog\Models\OutboundClick;
use App\Domain\Occasions\Models\Occasion;
use App\Domain\Recommendations\Models\RecommendationRun;
use App\Domain\Reminders\Models\DeviceToken;
use App\Domain\Reminders\Models\QueuedNotification;
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
        $user->load(['settings', 'publicProfile.submissions', 'wishlistItems']);

        // Și persoanele șterse din aplicație: le mai păstrăm (un reimport din
        // agendă le readuce), deci fac parte din ce deținem despre utilizator.
        $people = $user->people()->withTrashed()->with([
            'interests', 'avoids.interest', 'occasions.nameDay', 'occasions.holiday',
            'giftHistory', 'giftIdeas', 'fieldSources',
        ])->get();

        $nameOf = fn (?int $personId) => $personId === null ? null : $people->firstWhere('id', $personId)?->display_name;

        return [
            'exported_at' => now()->toIso8601String(),
            'format'      => 'wishio/export/2',

            'account' => [
                'name'                => $user->name,
                'email'               => $user->email,
                'locale'              => $user->locale,
                'country_code'        => $user->country_code,
                'timezone'            => $user->timezone,
                'name_day_calendar'   => $user->name_day_calendar,
                'birth_date'          => $user->birth_date?->toDateString(),
                'ai_consent'          => $user->ai_consent_at !== null,
                'ai_consent_at'       => $user->ai_consent_at?->toIso8601String(),
                'ai_consent_asked_at' => $user->ai_consent_asked_at?->toIso8601String(),
                'registered_at'       => $user->created_at?->toIso8601String(),
            ],

            'settings' => $user->settings ? [
                'reminder_days'  => $user->settings->reminderDays(),
                'preferred_hour' => $user->settings->preferred_hour,
                'quiet_hours'    => [$user->settings->quiet_from, $user->settings->quiet_to],
                'push_enabled'   => $user->settings->push_enabled,
                'email_digest'   => $user->settings->email_digest,
            ] : null,

            'people' => $people->map(fn ($person) => [
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
                // Ce să evite: un cod din listă sau un text scris de mână.
                'avoids' => $person->avoids->map(fn ($avoid) => [
                    'interest' => $avoid->interest?->code,
                    'text'     => $avoid->free_text,
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
                'from_public_link' => $person->from_public_link,
                'added_at'         => $person->created_at?->toIso8601String(),
                'archived_at'      => $person->archived_at?->toIso8601String(),
                'deleted_at'       => $person->deleted_at?->toIso8601String(),
            ])->values(),

            'wishlist' => $user->wishlistItems->map(fn ($item) => [
                'kind'       => $item->kind, 'title' => $item->title,
                'url'        => $item->url, 'priority' => $item->priority,
                'visibility' => $item->visibility, 'note' => $item->note,
            ])->values(),

            // Sărbătorile urmărite: ocazii care nu țin de o persoană.
            'holidays' => Occasion::query()
                ->where('user_id', $user->id)->whereNull('person_id')->with('holiday')->get()
                ->map(fn ($occasion) => [
                    'holiday' => $occasion->holiday?->label($user->locale),
                    'date'    => sprintf('%02d-%02d', $occasion->month, $occasion->day),
                    'muted'   => (bool) $occasion->is_muted,
                ])->values(),

            // Fără tokenul în sine: e un identificator tehnic, iar fișierul
            // pleacă prin foaia de partajare, spre orice aplicație.
            'devices' => DeviceToken::where('user_id', $user->id)->get()->map(fn ($device) => [
                'platform'       => $device->platform,
                'locale'         => $device->locale,
                'registered_at'  => $device->created_at?->toIso8601String(),
                'last_active_at' => $device->last_active_at?->toIso8601String(),
            ])->values(),

            'reminders' => QueuedNotification::where('user_id', $user->id)
                ->with('occasion')->orderBy('scheduled_for')->get()
                ->map(fn ($reminder) => [
                    'channel'       => $reminder->channel,
                    'person'        => $nameOf($reminder->occasion?->person_id),
                    'occasion'      => $reminder->occasion?->type,
                    'days_before'   => $reminder->days_before,
                    'scheduled_for' => $reminder->scheduled_for?->toIso8601String(),
                    'sent_at'       => $reminder->sent_at?->toIso8601String(),
                ])->values(),

            'recommendations' => RecommendationRun::where('user_id', $user->id)
                ->with('items.product')->latest()->get()
                ->map(fn ($run) => [
                    'person'   => $nameOf($run->person_id),
                    'kind'     => $run->kind,
                    'budget'   => [$run->budget_min, $run->budget_max],
                    'status'   => $run->status,
                    'products' => $run->items->sortBy('rank')->map(fn ($item) => $item->product?->title)->filter()->values(),
                    'at'       => $run->created_at?->toIso8601String(),
                ])->values(),

            'clicks' => OutboundClick::where('user_id', $user->id)
                ->with(['offer.product', 'merchant'])->latest()->get()
                ->map(fn ($click) => [
                    'shop'    => $click->merchant?->name,
                    'product' => $click->offer?->product?->title,
                    'price'   => $click->price,
                    'person'  => $nameOf($click->person_id),
                    'context' => $click->context,
                    'at'      => $click->created_at?->toIso8601String(),
                ])->values(),

            'public_profile' => $user->publicProfile ? [
                'url'          => url('/@'.$user->publicProfile->slug),
                'display_name' => $user->publicProfile->display_name,
                'active'       => $user->publicProfile->is_active,
                'visibility'   => $user->publicProfile->visibility,
                'views'        => $user->publicProfile->view_count,
                'submissions'  => $user->publicProfile->submissions->map(fn ($s) => [
                    'name'            => $s->display_name,
                    'birth_date'      => $s->birth_date?->toDateString(),
                    'interests'       => $s->interest_codes,
                    'message'         => $s->message,
                    'consent_version' => $s->consent_version,
                    'submitted_at'    => $s->created_at?->toIso8601String(),
                    'accepted_at'     => $s->accepted_at?->toIso8601String(),
                ])->values(),
            ] : null,
        ];
    }
}
