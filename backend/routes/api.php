<?php

use Illuminate\Support\Facades\Route;

Route::get('/v1/ping', function () {
    return response()->json([
        'ok'      => true,
        'locale'  => app()->getLocale(),
        'country' => config('wishio.market.default_country'),
        // Dovada ca i18n functioneaza cap-coada, inclusiv pluralul rusesc.
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
