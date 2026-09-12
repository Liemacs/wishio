<?php

namespace App\Http\Controllers;

use App\Domain\Validation\Models\GiftRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class LandingController extends Controller
{
    /** Versiunea textului de consimtamant acceptat — se schimba odata cu textul. */
    private const CONSENT_VERSION = '2026-09-1';

    public function show(): View|\Illuminate\Contracts\View\View
    {
        return view('landing.index', [
            'locales' => config('wishio.locales.supported'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'relationship'     => ['required', 'string', 'max:32'],
            'age_bracket'      => ['nullable', 'string', 'max:16'],
            'recipient_gender' => ['nullable', 'in:m,f'],
            'about'            => ['required', 'string', 'min:10', 'max:2000'],
            'budget'           => ['required', 'string', 'max:16'],
            'occasion'         => ['nullable', 'string', 'max:32'],
            'occasion_date'    => ['nullable', 'date', 'after_or_equal:today'],
            'contact'          => ['required', 'string', 'max:190'],
            'consent'          => ['accepted'],
        ], [], [
            'about'   => __('landing.form.about'),
            'contact' => __('landing.form.contact'),
        ]);

        [$min, $max] = $this->parseBudget($data['budget']);

        GiftRequest::create([
            'locale'           => app()->getLocale(),
            'relationship'     => $data['relationship'],
            'age_bracket'      => $data['age_bracket'] ?? null,
            'recipient_gender' => $data['recipient_gender'] ?? null,
            'about'            => $data['about'],
            'budget_min'       => $min,
            'budget_max'       => $max,
            'occasion'         => $data['occasion'] ?? null,
            'occasion_date'    => $data['occasion_date'] ?? null,
            'contact'          => $data['contact'],
            'contact_channel'  => $this->guessChannel($data['contact']),
            'consent_version'  => self::CONSENT_VERSION,
            'consented_at'     => now(),
            'source'           => substr((string) $request->query('src'), 0, 64) ?: null,
            // Doar hash, pentru limitarea abuzului. Nu stocam IP-ul in clar.
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
        ]);

        return redirect()->route('landing.thanks');
    }

    /** @return array{int|null,int|null} */
    private function parseBudget(string $budget): array
    {
        return match ($budget) {
            'under_500' => [null, 500],
            '500_1000'  => [500, 1000],
            '1000_2000' => [1000, 2000],
            '2000_5000' => [2000, 5000],
            'over_5000' => [5000, null],
            default     => [null, null],
        };
    }

    private function guessChannel(string $contact): string
    {
        // Numerele se scriu cu spatii si paranteze: "+373 69 123 456".
        $digits = preg_replace('/\D/', '', $contact) ?? '';

        return match (true) {
            str_contains($contact, '@') && str_contains($contact, '.') => 'email',
            str_starts_with(ltrim($contact), '@')                      => 'telegram',
            mb_strlen($digits) >= 8                                    => 'phone',
            default                                                    => 'other',
        };
    }
}
