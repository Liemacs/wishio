<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Localizare
    |--------------------------------------------------------------------------
    | RO este limba de baza si sursa de adevar pentru traduceri.
    | RU si EN au paritate functionala, cu fallback la RO.
    | Vezi docs/05-arhitectura.md § 6.
    */
    'locales' => [
        'supported' => explode(',', env('WISHIO_SUPPORTED_LOCALES', 'ro,ru,en')),
        'default'   => 'ro',
        'fallback'  => 'ro',
        'names'     => [
            'ro' => 'Romana',
            'ru' => 'Русский',
            'en' => 'English',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Piata
    |--------------------------------------------------------------------------
    | Totul este multi-country de la inceput, chiar daca lansam intr-o tara.
    | Vezi docs/02-strategie-riscuri.md § 1.
    */
    'market' => [
        'default_country'  => env('WISHIO_DEFAULT_COUNTRY', 'MD'),
        'default_currency' => env('WISHIO_DEFAULT_CURRENCY', 'MDL'),
        'default_timezone' => 'Europe/Chisinau',
    ],

    /*
    |--------------------------------------------------------------------------
    | Identitate contacte
    |--------------------------------------------------------------------------
    | Agenda telefonului NU se urca pe server. Doar HMAC-uri ale numerelor
    | normalizate E.164, si doar pentru contactele selectate explicit de user.
    | Vezi docs/04-model-domeniu.md § 2 si docs/06-privacy-legal.md.
    */
    'identity' => [
        'hash_pepper' => env('WISHIO_CONTACT_HASH_PEPPER'),
        'hash_algo'   => 'sha256',
    ],

    /*
    |--------------------------------------------------------------------------
    | Remindere — anti-spam
    |--------------------------------------------------------------------------
    */
    'reminders' => [
        'default_days_before'    => [7, 3, 1],
        'max_per_occasion'       => 4,
        'max_push_per_day'       => 2,
        'quiet_hours'            => ['from' => 22, 'to' => 8],
        'min_confidence_to_push' => 0.75,
    ],

    'push' => [
        // 'null' (implicit) sau 'expo'
        'driver' => env('WISHIO_PUSH_DRIVER', 'null'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI
    |--------------------------------------------------------------------------
    | Contextul trimis spre AI este pseudonimizat: fara nume, telefoane,
    | emailuri sau note libere. Vezi docs/05-arhitectura.md § 5.
    */
    'ai' => [
        'provider'         => env('WISHIO_AI_PROVIDER', 'null'),
        'model'            => env('WISHIO_AI_MODEL'),
        'api_key'          => env('WISHIO_AI_API_KEY'),
        'max_cost_per_run' => (float) env('WISHIO_AI_MAX_COST_PER_RUN', 0.02),
        // Plafon zilnic. 0 = fără plafon. La depășire, produsul trece pe ruta
        // fără AI și continuă să funcționeze.
        'max_cost_per_day' => (float) env('WISHIO_AI_MAX_COST_PER_DAY', 5.00),
        'timeout'          => 20,
        'send_free_notes'  => false, // notele pot contine date sensibile
    ],

    /*
    |--------------------------------------------------------------------------
    | Catalog
    |--------------------------------------------------------------------------
    */
    'catalog' => [
        'adapter'             => env('WISHIO_CATALOG_ADAPTER', 'manual'),
        'stale_after_days'    => 7,
        'sync_interval_hours' => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Recomandari — ponderile scorului determinist
    |--------------------------------------------------------------------------
    | Vezi docs/05-arhitectura.md § 3. Suma trebuie sa fie 1.0.
    */
    'recommendations' => [
        'weights' => [
            'interest_overlap' => 0.45,
            'budget_fit'       => 0.20,
            'gift_score'       => 0.15,
            'freshness'        => 0.10,
            'sponsored'        => 0.10,
        ],
        'max_per_category' => 2,
        'max_per_merchant' => 2,
        'result_size'      => 8,
    ],

];
