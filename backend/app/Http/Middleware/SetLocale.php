<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rezolva limba cererii: Accept-Language -> users.locale -> RO.
 *
 * RO este limba de baza si fallback-ul universal.
 * Vezi docs/05-arhitectura.md § 6 si CLAUDE.md regula 1.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolve($request));

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        /** @var list<string> $supported */
        $supported = config('wishio.locales.supported');

        // 1. Alegere explicita in sesiune (comutatorul de limba din web).
        //    Are prioritate: utilizatorul a decis, nu presupunem noi.
        $session = $request->hasSession() ? $request->session()->get('locale') : null;
        if (is_string($session) && in_array($session, $supported, true)) {
            return $session;
        }

        // 2. Header Accept-Language — mobile trimite locale-ul ales de utilizator.
        //    Verificam intai ca headerul EXISTA: fara el, getPreferredLanguage()
        //    intoarce locale-ul implicit al lui Symfony ('en'), nu primul element
        //    din lista noastra. Fara verificarea asta, orice cerere fara header
        //    ar primi engleza in loc de romana.
        if (filled($request->header('Accept-Language'))) {
            $preferred = $request->getPreferredLanguage($supported);

            if ($preferred !== null && in_array($preferred, $supported, true)) {
                return $preferred;
            }
        }

        // 3. Preferinta salvata a utilizatorului autentificat.
        $user = $request->user();
        if ($user !== null && in_array($user->locale ?? null, $supported, true)) {
            return $user->locale;
        }

        // 4. RO.
        return config('wishio.locales.default');
    }
}
