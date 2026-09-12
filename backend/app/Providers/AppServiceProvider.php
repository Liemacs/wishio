<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Implicit nu trimitem nimic. Expo se activează explicit prin
        // WISHIO_PUSH_DRIVER=expo, ca dezvoltarea locală și testele să nu
        // trimită accidental notificări pe telefoane reale.
        // Implicit: fără AI. Ruta pe reguli e completă și trebuie să rămână
        // funcțională oricum — vezi RuleBasedAiProvider.
        $this->app->bind(\App\Support\Ai\AiProvider::class, fn () => match (config('wishio.ai.provider')) {
            default => new \App\Support\Ai\RuleBasedAiProvider(),
        });

        $this->app->bind(\App\Support\Push\PushSender::class, fn () => match (config('wishio.push.driver')) {
            'expo'  => new \App\Support\Push\ExpoPushSender(),
            default => new \App\Support\Push\NullPushSender(),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
