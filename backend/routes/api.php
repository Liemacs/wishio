<?php

use App\Http\Api\V1\Controllers\AuthController;
use App\Http\Api\V1\Controllers\InterestController;
use App\Http\Api\V1\Controllers\PersonController;
use App\Http\Api\V1\Controllers\PersonInterestController;
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

        // Taxonomia pentru selectorul de interese, in limba cererii.
        Route::get('/interests', [InterestController::class, 'index']);

        Route::apiResource('people', PersonController::class);
        Route::put('/people/{person}/interests', [PersonInterestController::class, 'update']);
    });
});
