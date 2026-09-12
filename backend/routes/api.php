<?php

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

    Route::middleware('auth:sanctum')->group(function () {
        // Taxonomia pentru selectorul de interese, in limba cererii.
        Route::get('/interests', [InterestController::class, 'index']);

        Route::apiResource('people', PersonController::class);
        Route::put('/people/{person}/interests', [PersonInterestController::class, 'update']);
    });
});
