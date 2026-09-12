<?php

use App\Domain\Reminders\Models\UserSettings;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PublicProfileController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Faza 0 — validare
|--------------------------------------------------------------------------
| Landing-ul de validare concierge (docs/14). Temporar: se scoate dupa ce
| trecem porțile G0–G2, impreuna cu tabelul `gift_requests`.
*/

Route::get('/', [LandingController::class, 'show'])->name('landing');

Route::post('/cerere', [LandingController::class, 'store'])
    ->middleware('throttle:5,10')       // max 5 cereri / 10 minute per IP
    ->name('landing.request');

Route::view('/multumim', 'landing.thanks')->name('landing.thanks');

// Comutator de limba: seteaza preferinta si revine de unde a plecat.
Route::get('/lang/{locale}', function (string $locale) {
    abort_unless(in_array($locale, config('wishio.locales.supported'), true), 404);

    session(['locale' => $locale]);

    return back();
})->name('locale.switch');

/*
|--------------------------------------------------------------------------
| Dezabonare de la rezumatul săptămânal
|--------------------------------------------------------------------------
| Link semnat: funcționează dintr-un singur click, fără autentificare.
| Fără el, „dezabonează-mă” ar însemna „intră în cont și caută setarea”.
*/
Route::get('/digest/unsubscribe/{user}', function (User $user) {
    UserSettings::updateOrCreate(
        ['user_id' => $user->id],
        ['email_digest' => false]
    );

    app()->setLocale($user->locale);

    return view('mail.unsubscribed');
})->name('digest.unsubscribe')->middleware('signed');

/*
|--------------------------------------------------------------------------
| Pagini publice de profil
|--------------------------------------------------------------------------
| Se deschid fara cont si fara aplicatie. Vezi docs/01 § Reframe 2.
*/
Route::get('/@{slug}', [PublicProfileController::class, 'show'])->name('profile.show');

Route::post('/@{slug}', [PublicProfileController::class, 'store'])
    ->middleware('throttle:10,10')
    ->name('profile.store');

Route::get('/@{slug}/multumim/{token}', [PublicProfileController::class, 'thanks'])->name('profile.thanks');

Route::get('/@{slug}/sterge/{token}', [PublicProfileController::class, 'destroy'])->name('profile.destroy');

/*
|--------------------------------------------------------------------------
| Asocierea aplicatiei cu domeniul
|--------------------------------------------------------------------------
| Fara aceste fisiere, linkul `wishio.md/@slug` se deschide doar in browser.
| Raspund 404 pana cand exista un build semnat si identificatorii configurati:
| un fisier gresit strica asocierea mai rau decat lipsa lui.
*/
Route::get('/.well-known/apple-app-site-association', function () {
    $appId = config('wishio.deep_links.ios_app_id');

    abort_if(blank($appId), 404);

    return response()->json([
        'applinks' => [
            'apps'    => [],
            'details' => [[
                'appID' => $appId,
                'paths' => ['/@*'],
            ]],
        ],
    ])->header('Content-Type', 'application/json');
});

Route::get('/.well-known/assetlinks.json', function () {
    $fingerprint = config('wishio.deep_links.android_sha256_fingerprint');

    abort_if(blank($fingerprint), 404);

    return response()->json([[
        'relation' => ['delegate_permission/common.handle_all_urls'],
        'target'   => [
            'namespace'                => 'android_app',
            'package_name'             => config('wishio.deep_links.android_package'),
            'sha256_cert_fingerprints' => [$fingerprint],
        ],
    ]]);
});
