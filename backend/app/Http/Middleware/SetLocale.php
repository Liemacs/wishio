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

        // 1. Header explicit — mobile trimite locale-ul ales de utilizator.
        $preferred = $request->getPreferredLanguage($supported);
        if ($preferred !== null && in_array($preferred, $supported, true)) {
            return $preferred;
        }

        // 2. Preferinta salvata a utilizatorului autentificat.
        $user = $request->user();
        if ($user !== null && in_array($user->locale ?? null, $supported, true)) {
            return $user->locale;
        }

        // 3. RO.
        return config('wishio.locales.default');
    }
}
