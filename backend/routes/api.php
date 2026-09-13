<?php

use App\Http\Api\V1\Controllers\AccountController;
use App\Http\Api\V1\Controllers\AuthController;
use App\Http\Api\V1\Controllers\ContactImportController;
use App\Http\Api\V1\Controllers\GiftController;
use App\Http\Api\V1\Controllers\InterestController;
use App\Http\Api\V1\Controllers\MyProfileController;
use App\Http\Api\V1\Controllers\OccasionController;
use App\Http\Api\V1\Controllers\PersonAnalysisController;
use App\Http\Api\V1\Controllers\PersonController;
use App\Http\Api\V1\Controllers\PersonInterestController;
use App\Http\Api\V1\Controllers\ProductController;
use App\Http\Api\V1\Controllers\RecommendationController;
use App\Http\Api\V1\Controllers\SettingsController;
use App\Http\Api\V1\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/ping', function () {
        return response()->json([
            'ok'      => true,
            'locale'  => app()->getLocale(),
            'country' => config('wishio.market.default_country'),
            'sample'  => [
                'occasion' => __('wishio.occasions.birthday'),
                'reminder' => trans_choice('wishio.reminder.days_before', 5, [
                    'name'     => 'Alex',
                    'occasion' => __('wishio.occasions.birthday'),
                    'count'    => 5,
                ]),
            ],
        ]);
    });

    // Autentificare simpla pentru dezvoltare. Apple / Google / email OTP la S1.8;
    // contractul cu tokenul Bearer ramane acelasi, deci ecranele nu se schimba.
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:5,10');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,10');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::patch('/auth/me', [AuthController::class, 'updateMe']);

        // Drepturile utilizatorului asupra datelor lui (docs/06 § 7).
        Route::get('/account/export', [AccountController::class, 'export']);
        Route::delete('/account', [AccountController::class, 'destroy']);

        // Taxonomia pentru selectorul de interese, in limba cererii.
        Route::get('/interests', [InterestController::class, 'index']);

        Route::apiResource('people', PersonController::class);
        Route::put('/people/{person}/interests', [PersonInterestController::class, 'update']);

        // Idei de cadou si istoric (S10)
        Route::get('/people/{person}/gifts', [GiftController::class, 'index']);
        Route::post('/people/{person}/ideas', [GiftController::class, 'storeIdea']);
        Route::post('/people/{person}/history', [GiftController::class, 'storeHistory']);
        Route::get('/ideas', [GiftController::class, 'ideas']);
        Route::patch('/ideas/{idea}', [GiftController::class, 'updateIdea']);
        Route::delete('/ideas/{idea}', [GiftController::class, 'destroyIdea']);
        Route::post('/ideas/{idea}/given', [GiftController::class, 'given']);
        Route::delete('/history/{entry}', [GiftController::class, 'destroyHistory']);

        // Import din agenda telefonului: doar contactele alese explicit, si
        // doar nume + zi de nastere (docs/00 § D-017).
        Route::post('/contacts/import', [ContactImportController::class, 'store']);

        // „Spune-mi despre Alex” -> interese propuse spre confirmare
        Route::post('/people/{person}/analyze', [PersonAnalysisController::class, 'store']);

        // Recomandari
        Route::post('/people/{person}/recommendations', [RecommendationController::class, 'store']);
        Route::get('/recommendations/{recommendation}', [RecommendationController::class, 'show']);
        Route::post('/ai-consent', [RecommendationController::class, 'consent']);

        // Catalog
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
        Route::post('/offers/{offer}/click', [ProductController::class, 'click']);

        // Profilul public propriu si lista de dorinte
        Route::get('/profile', [MyProfileController::class, 'show']);
        Route::patch('/profile', [MyProfileController::class, 'update']);
        Route::post('/wishlist', [MyProfileController::class, 'storeWishlistItem']);
        Route::delete('/wishlist/{item}', [MyProfileController::class, 'destroyWishlistItem']);

        // „Cine este?” — completarile care asteapta alegerea proprietarului (S9.8)
        Route::get('/submissions/pending', [SubmissionController::class, 'pending']);
        Route::post('/submissions/{submission}/resolve', [SubmissionController::class, 'resolve']);

        Route::get('/settings', [SettingsController::class, 'show']);
        Route::patch('/settings', [SettingsController::class, 'update']);
        Route::post('/devices', [SettingsController::class, 'storeDevice']);
        Route::delete('/devices', [SettingsController::class, 'destroyDevice']);

        Route::get('/occasions', [OccasionController::class, 'index']);
        Route::patch('/occasions/{occasion}', [OccasionController::class, 'update']);
    });
});
