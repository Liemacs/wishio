<?php

namespace App\Http\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Ce trebuie să știe aplicația înainte de orice ecran (docs/10 § 5).
 *
 * Sub `min_supported_version`, aplicația arată doar ecranul de actualizare.
 * Fără acest mecanism în primul build publicat, versiunea aceea n-ar mai
 * putea fi oprită niciodată, oricât s-ar schimba API-ul.
 */
class AppConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => [
            'min_supported_version' => config('wishio.app.min_supported_version'),
            'store_url'             => [
                'ios'     => config('wishio.app.store_url.ios'),
                'android' => config('wishio.app.store_url.android'),
            ],
        ]]);
    }
}
