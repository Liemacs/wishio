<?php

namespace App\Http\Controllers;

use App\Domain\People\Models\Interest;
use App\Domain\People\Models\InterestGroup;
use App\Domain\Profiles\Actions\AcceptSubmission;
use App\Domain\Profiles\Actions\WithdrawSubmission;
use App\Domain\Profiles\Models\ProfileSubmission;
use App\Domain\Profiles\Models\PublicProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Pagina publică `@slug`. Motorul de creștere din docs/01 § Reframe 2.
 *
 * Se deschide fără cont și fără aplicație — asta e tot rostul ei. Dacă
 * prietenul tău ar trebui să instaleze ceva ca să-ți spună ziua lui, bucla
 * virală moare înainte să înceapă.
 */
class PublicProfileController extends Controller
{
    private const CONSENT_VERSION = '2026-09-1';

    public function show(Request $request, string $slug): View
    {
        $profile = PublicProfile::with('user')->where('slug', $slug)->first();

        if (! $profile || ! $profile->is_active) {
            return view('profile.inactive');
        }

        // Numărătoare simplă, fără cookie și fără identificarea vizitatorului.
        $profile->increment('view_count');

        return view('profile.show', [
            'profile'   => $profile,
            'interests' => InterestGroup::with('interests')
                ->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $profile = PublicProfile::where('slug', $slug)->firstOrFail();

        abort_unless($profile->is_active, 404);

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'birthday'     => ['nullable', 'string', 'max:16'],
            'interests'    => ['nullable', 'array', 'max:12'],
            'interests.*'  => ['string', Rule::in(Interest::validCodes())],
            'message'      => ['nullable', 'string', 'max:280'],
            'consent'      => ['accepted'],
        ]);

        [$birthDate, $yearKnown] = $this->parseBirthday($data['birthday'] ?? null);

        $submission = ProfileSubmission::create([
            'public_profile_id' => $profile->id,
            'display_name'      => $data['display_name'],
            'birth_date'        => $birthDate,
            'birth_year_known'  => $yearKnown,
            'interest_codes'    => $data['interests'] ?? [],
            'message'           => $data['message'] ?? null,
            'locale'            => app()->getLocale(),
            'consent_version'   => self::CONSENT_VERSION,
            'consented_at'      => now(),
            'delete_token'      => Str::random(48),
            'ip_hash'           => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
        ]);

        // Datele ajung imediat la destinatar: dacă ar aștepta o aprobare,
        // jumătate din completări s-ar pierde în coadă.
        app(AcceptSubmission::class)($submission);

        return redirect()->route('profile.thanks', [
            'slug'  => $slug,
            'token' => $submission->delete_token,
        ]);
    }

    public function thanks(Request $request, string $slug, string $token): View
    {
        $profile = PublicProfile::with('user')->where('slug', $slug)->firstOrFail();

        return view('profile.thanks', ['profile' => $profile, 'token' => $token]);
    }

    /**
     * Ștergerea, fără cont și fără autentificare.
     *
     * Cine a completat un formular trebuie să poată retrage datele la fel de
     * ușor cum le-a dat. Vezi docs/06 § 5.
     */
    public function destroy(string $slug, string $token): View
    {
        $submission = ProfileSubmission::where('delete_token', $token)->firstOrFail();
        $profile = $submission->profile->load('user');

        // Ce se șterge și ce rămâne din persoană decide acțiunea, nu controllerul.
        app(WithdrawSubmission::class)($submission);

        return view('profile.deleted', ['profile' => $profile]);
    }

    /** @return array{?string, bool} */
    private function parseBirthday(?string $input): array
    {
        if (! $input || ! preg_match('/^(\d{1,2})[.\-\/](\d{1,2})(?:[.\-\/](\d{4}))?$/', trim($input), $m)) {
            return [null, false];
        }

        [, $day, $month, $year] = array_pad($m, 4, null);

        if ((int) $day < 1 || (int) $day > 31 || (int) $month < 1 || (int) $month > 12) {
            return [null, false];
        }

        // Fără an folosim un an bisect, ca 29 februarie să rămână valid.
        return [sprintf('%s-%02d-%02d', $year ?? '2000', $month, $day), $year !== null];
    }
}
