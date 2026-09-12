<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [
            SetLocale::class,
        ]);
        $middleware->web(append: [
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Middleware-ul `throttle` raspunde cu un text fix, in engleza
        // („Too Many Attempts.”), pe care aplicatia l-ar afisa ca atare.
        // Il inlocuim cu unul tradus; statusul 429 si headerele raman
        // (Retry-After, X-RateLimit-*). CLAUDE.md, regula 1.
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            // Limitarea ruleaza inaintea SetLocale (ordinea vine din prioritatea
            // middleware-urilor din framework), deci limba nu e inca rezolvata.
            app()->setLocale(app(SetLocale::class)->resolve($request));

            // In secunda in care expira fereastra, Retry-After poate fi 0.
            $seconds = max(1, (int) ($e->getHeaders()['Retry-After'] ?? 0));
            $message = trans_choice('auth.throttle', $seconds, ['seconds' => $seconds]);

            return $request->expectsJson()
                ? response()->json(['message' => $message], $e->getStatusCode(), $e->getHeaders())
                : response()->view('errors.throttle', ['message' => $message], $e->getStatusCode(), $e->getHeaders());
        });
    })->create();
