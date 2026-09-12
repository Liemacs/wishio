<?php

use App\Domain\Reminders\Models\UserSettings;
use App\Http\Controllers\LandingController;
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
