<?php

namespace App\Http\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Autentificare simplă cu email și parolă, pentru dezvoltare.
 *
 * TEMPORAR. Metodele reale din `docs/03-scop.md` — Sign in with Apple, Google
 * și email OTP — vin la S1.8. Pe iOS, Apple este obligatoriu dacă există orice
 * alt social login, deci ruta aceasta nu poate rămâne singura la lansare.
 *
 * Contractul (token Bearer în `Authorization`) nu se schimbă însă odată cu
 * metodele, deci ecranele construite acum rămân valabile.
 */
class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:190', Rule::unique('users')],
            'password' => ['required', 'string', 'min:8'],
            'locale'   => ['nullable', Rule::in(config('wishio.locales.supported'))],
        ]);

        $locale = $data['locale'] ?? app()->getLocale();

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'locale'   => $locale,
            // Calendarul implicit se alege din limbă: vorbitorii de rusă din
            // Moldova urmează în general stilul vechi. Se poate schimba oricând.
            'name_day_calendar' => $locale === 'ru' ? 'orthodox_old' : 'orthodox_new',
        ]);

        return $this->tokenResponse($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        // Acelasi mesaj pentru email inexistent si parola gresita: altfel
        // endpointul spune atacatorului care adrese sunt inregistrate.
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return $this->tokenResponse($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(status: 204);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->profile($request->user())]);
    }

    public function updateMe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'              => ['sometimes', 'string', 'max:120'],
            'locale'            => ['sometimes', Rule::in(config('wishio.locales.supported'))],
            'name_day_calendar' => ['sometimes', Rule::in(['orthodox_new', 'orthodox_old', 'catholic'])],
            'birth_date'        => ['sometimes', 'nullable', 'date', 'before:tomorrow'],
            'timezone'          => ['sometimes', 'string', 'max:48'],
        ]);

        $request->user()->update($data);

        return response()->json(['data' => $this->profile($request->user()->fresh())]);
    }

    private function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'data'  => $this->profile($user),
        ], $status);
    }

    private function profile(User $user): array
    {
        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'locale'            => $user->locale,
            'country_code'      => $user->country_code,
            'timezone'          => $user->timezone,
            'name_day_calendar' => $user->name_day_calendar,
            'birth_date'        => $user->birth_date?->format('Y-m-d'),
            // Două stări distincte: dacă a fost întrebat și ce a răspuns.
            'ai_consent'       => $user->ai_consent_at !== null,
            'ai_consent_asked' => $user->ai_consent_asked_at !== null,
        ];
    }
}
