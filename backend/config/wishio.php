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

    /*
    |--------------------------------------------------------------------------
    | Retenție
    |--------------------------------------------------------------------------
    | Termenele promise în politica de confidențialitate și în docs/21. Se
    | aplică singure, în fiecare noapte (routes/console.php). Nu le lungi fără
    | să schimbi întâi politica.
    */
    'retention' => [
        'clicks_months'             => 24,   // M-08: apoi doar totalul pe lună
        'notification_queue_months' => 13,   // M-11
        'inactive_token_months'     => 12,   // M-11
        'deleted_people_days'       => 30,   // M-07, D-024
        'pending_submission_days'   => 30,   // M-10, D-024
    ],

    /*
    |--------------------------------------------------------------------------
    | Deep links
    |--------------------------------------------------------------------------
    | Ca linkul `wishio.md/@slug` să deschidă aplicația când e instalată,
    | domeniul trebuie să servească fișierele de asociere. Rămân goale până
    | când există un build semnat — vezi docs/18 § 7.
    */
    /*
    |--------------------------------------------------------------------------
    | Identitatea operatorului de date
    |--------------------------------------------------------------------------
    | Legea 195/2024 cere un operator identificabil. Se completeaza cand exista
    | entitatea juridica (docs/08 § A7).
    */
    'legal' => [
        'operator_name'    => env('WISHIO_OPERATOR_NAME'),
        'operator_address' => env('WISHIO_OPERATOR_ADDRESS'),
        'contact_email'    => env('WISHIO_PRIVACY_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Contul demo
    |--------------------------------------------------------------------------
    | Pentru App Review și capturile din store: `php artisan wishio:demo`.
    | Parola nu stă în repo; fără ea, comanda generează una și o afișează.
    */
    'demo' => [
        'password' => env('WISHIO_DEMO_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagina principală a site-ului
    |--------------------------------------------------------------------------
    | `app`: prezentarea aplicației, cu butoanele spre magazine (lansarea,
    | S12.5). `concierge`: landing-ul din Faza 0, cu formularul de cereri
    | (docs/17), pentru când validarea se reia.
    */
    'landing' => env('WISHIO_LANDING', 'app'),

    /*
    |--------------------------------------------------------------------------
    | Versiunea aplicației mobile
    |--------------------------------------------------------------------------
    | Sub versiunea minimă, aplicația arată doar ecranul de actualizare. Se
    | ridică numai când o schimbare de API ar strica versiunile vechi — altfel
    | oamenii sunt trimiși în store fără motiv. Linkul din App Store se
    | cunoaște abia după ce aplicația e creată în App Store Connect.
    */
    'app' => [
        'min_supported_version' => env('WISHIO_MIN_APP_VERSION', '0.1.0'),
        'store_url'             => [
            'ios' => env('WISHIO_APP_STORE_URL'),
            // Goale până la publicare: fără ele, site-ul nu arată butoane spre magazine.
            'android' => env('WISHIO_PLAY_STORE_URL'),
        ],
    ],

    'deep_links' => [
        'ios_app_id'                 => env('WISHIO_IOS_APP_ID'),        // TEAMID.md.wishio.app
        'android_package'            => env('WISHIO_ANDROID_PACKAGE', 'md.wishio.app'),
        'android_sha256_fingerprint' => env('WISHIO_ANDROID_SHA256'),
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
