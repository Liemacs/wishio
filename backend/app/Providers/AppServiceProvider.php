<?php

namespace App\Providers;

use App\Support\Ai\AiProvider;
use App\Support\Ai\RuleBasedAiProvider;
use App\Support\Push\ExpoPushSender;
use App\Support\Push\NullPushSender;
use App\Support\Push\PushSender;
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
        $this->app->bind(AiProvider::class, fn () => match (config('wishio.ai.provider')) {
            default => new RuleBasedAiProvider,
        });

        $this->app->bind(PushSender::class, fn () => match (config('wishio.push.driver')) {
            'expo'  => new ExpoPushSender,
            default => new NullPushSender,
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
