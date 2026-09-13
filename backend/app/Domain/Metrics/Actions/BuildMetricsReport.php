<?php

namespace App\Domain\Metrics\Actions;

use App\Domain\Catalog\Models\OutboundClick;
use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Models\GiftHistory;
use App\Domain\People\Models\GiftIdea;
use App\Domain\People\Models\Person;
use App\Domain\Profiles\Models\ProfileSubmission;
use App\Domain\Recommendations\Models\RecommendationRun;
use App\Domain\Reminders\Models\DeviceToken;
use App\Domain\Reminders\Models\QueuedNotification;
use App\Domain\Reminders\Models\UserSettings;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pâlnia și tabloul săptămânal din docs/07, calculate din baza de date.
 *
 * Aplicația nu are SDK de analytics (docs/20, P9), iar ce contează pentru
 * porțile G3–G5 se vede deja în datele serviciului. Conturile interne — demo,
 * review, echipă, toate pe domeniul wishio.md — nu intră în cifre.
 */
class BuildMetricsReport
{
    public const INTERNAL_DOMAIN = '@wishio.md';

    /**
     * Pașii pâlniei pentru conturile create în interval. `of` e baza ratei:
     * cohorta, cu excepția deschiderilor, raportate la reminderele trimise.
     *
     * @return list<array{key: string, count: int, of: int, target: ?float}>
     */
    public function funnel(CarbonImmutable $since, CarbonImmutable $until): array
    {
        $users = $this->users()->whereBetween('created_at', [$since, $until])->pluck('id');
        $total = $users->count();

        $step = fn (string $key, int $count, ?float $target = null, ?int $of = null) => [
            'key' => $key, 'count' => $count, 'of' => $of ?? $total, 'target' => $target,
        ];

        $sent = fn () => QueuedNotification::whereIn('user_id', $users)
            ->where('channel', 'push')
            ->whereNotNull('sent_at')
            ->whereNull('failure');

        return [
            $step('accounts', $total),
            $step('people_5', $this->withAtLeast(Person::query(), $users, 5), 0.60),
            $step('occasions_8', $this->withAtLeast(Occasion::query()->whereNotNull('person_id')->whereNull('rejected_at'), $users, 8), 0.70),
            $step('push_enabled', DeviceToken::whereIn('user_id', $users)
                ->whereNotIn('user_id', UserSettings::where('push_enabled', false)->select('user_id'))
                ->distinct()->count('user_id'), 0.55),
            $step('reminder_sent', $sent()->distinct()->count('user_id')),
            $step('reminder_opened', $sent()->whereNotNull('opened_at')->count(), 0.35, $sent()->count()),
            $step('recommendations', RecommendationRun::whereIn('user_id', $users)->distinct()->count('user_id'), 0.20),
            $step('clicks', OutboundClick::whereIn('user_id', $users)->distinct()->count('user_id'), 0.15),
            $step('gift_given', GiftHistory::whereIn('user_id', $users)->distinct()->count('user_id'), 0.08),
        ];
    }

    /**
     * Cele cinci cifre din docs/07 § 6, pentru ultimele 7 zile. Instalările din
     * linkuri personale nu se pot măsura fără atribuire; în locul lor, completările.
     *
     * @return array{active_users: int, people_acted_for: int, clicks: int, link_submissions: int, retention_cohort: int, retention_d30: int}
     */
    public function weekly(CarbonImmutable $now): array
    {
        $weekAgo = $now->subDays(7);
        $users = $this->users()->pluck('id');

        // Persoanele pentru care cineva a făcut ceva: a cerut idei, a salvat,
        // a deschis un magazin sau a marcat un cadou ca oferit.
        $people = collect()
            ->merge(RecommendationRun::whereIn('user_id', $users)->where('created_at', '>=', $weekAgo)->pluck('person_id'))
            ->merge(GiftIdea::whereIn('user_id', $users)->where('created_at', '>=', $weekAgo)->pluck('person_id'))
            ->merge(GiftHistory::whereIn('user_id', $users)->where('created_at', '>=', $weekAgo)->pluck('person_id'))
            ->merge(OutboundClick::whereIn('user_id', $users)->where('created_at', '>=', $weekAgo)->whereNotNull('person_id')->pluck('person_id'))
            ->unique();

        [$cohort, $retained] = $this->retentionD30($now);

        return [
            'active_users' => DB::table('personal_access_tokens')
                ->where('tokenable_type', (new User)->getMorphClass())
                ->whereIn('tokenable_id', $users)
                ->where('last_used_at', '>=', $weekAgo)
                ->distinct()
                ->count('tokenable_id'),
            'people_acted_for' => $people->count(),
            'clicks'           => OutboundClick::whereIn('user_id', $users)->where('created_at', '>=', $weekAgo)->count(),
            'link_submissions' => ProfileSubmission::where('created_at', '>=', $weekAgo)
                ->whereHas('profile', fn ($query) => $query->whereIn('user_id', $users))
                ->count(),
            'retention_cohort' => $cohort,
            'retention_d30'    => $retained,
        ];
    }

    private function users(): Builder
    {
        return User::query()->where('email', 'not like', '%'.self::INTERNAL_DOMAIN);
    }

    /** @param Collection<int, int> $users */
    private function withAtLeast(Builder $query, Collection $users, int $minimum): int
    {
        return $query->whereIn('user_id', $users)
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('count(*) >= ?', [$minimum])
            ->get()
            ->count();
    }

    /**
     * Cohorta creată acum 30–60 de zile și câți din ea au mai folosit aplicația
     * după ziua 30. Ultima folosire vine din tokenurile de acces: un utilizator
     * deconectat între timp nu mai apare, deci cifra e mai degrabă prudentă.
     *
     * @return array{int, int}
     */
    private function retentionD30(CarbonImmutable $now): array
    {
        $cohort = $this->users()
            ->whereBetween('created_at', [$now->subDays(60), $now->subDays(30)])
            ->get(['id', 'created_at']);

        $lastUse = DB::table('personal_access_tokens')
            ->where('tokenable_type', (new User)->getMorphClass())
            ->whereIn('tokenable_id', $cohort->pluck('id'))
            ->groupBy('tokenable_id')
            ->selectRaw('tokenable_id, max(last_used_at) as last_used')
            ->pluck('last_used', 'tokenable_id');

        $retained = $cohort->filter(fn (User $user) => isset($lastUse[$user->id])
            && CarbonImmutable::parse($lastUse[$user->id])->gte(CarbonImmutable::parse($user->created_at)->addDays(30)))
            ->count();

        return [$cohort->count(), $retained];
    }
}
