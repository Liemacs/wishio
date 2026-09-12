<?php

/**
 * Categorii de catalog, cu scorul de bază „cât de cadou” (1–5).
 *
 * Scorul de bază e primul strat din docs/05 § 4: nu ne uităm la fiecare produs,
 * ci la ce fel de lucru e. Consumabilele și piesele stau la 1 și nu ajung
 * niciodată în recomandări.
 */
return [
    ['code' => 'audio',          'score' => 4, 'ro' => 'Audio și căști',    'ru' => 'Аудио и наушники',      'en' => 'Audio & headphones'],
    ['code' => 'wearables',      'score' => 4, 'ro' => 'Ceasuri smart',     'ru' => 'Смарт-часы',            'en' => 'Smartwatches'],
    ['code' => 'computing',      'score' => 3, 'ro' => 'Calculatoare',      'ru' => 'Компьютеры',            'en' => 'Computers'],
    ['code' => 'gaming',         'score' => 4, 'ro' => 'Gaming',            'ru' => 'Игры',                  'en' => 'Gaming'],
    ['code' => 'fragrance',      'score' => 5, 'ro' => 'Parfumuri',         'ru' => 'Парфюмерия',            'en' => 'Fragrance'],
    ['code' => 'cosmetics',      'score' => 4, 'ro' => 'Cosmetice',         'ru' => 'Косметика',             'en' => 'Cosmetics'],
    ['code' => 'jewelry',        'score' => 5, 'ro' => 'Bijuterii',         'ru' => 'Украшения',             'en' => 'Jewelry'],
    ['code' => 'bags',           'score' => 4, 'ro' => 'Genți și rucsacuri','ru' => 'Сумки и рюкзаки',       'en' => 'Bags'],
    ['code' => 'footwear',       'score' => 3, 'ro' => 'Încălțăminte',      'ru' => 'Обувь',                 'en' => 'Footwear'],
    ['code' => 'kitchen',        'score' => 4, 'ro' => 'Bucătărie',         'ru' => 'Кухня',                 'en' => 'Kitchen'],
    ['code' => 'coffee',         'score' => 5, 'ro' => 'Cafea',             'ru' => 'Кофе',                  'en' => 'Coffee'],
    ['code' => 'home_decor',     'score' => 4, 'ro' => 'Decorațiuni',       'ru' => 'Декор',                 'en' => 'Home decor'],
    ['code' => 'fitness',        'score' => 4, 'ro' => 'Fitness',           'ru' => 'Фитнес',                'en' => 'Fitness'],
    ['code' => 'outdoor',        'score' => 4, 'ro' => 'Outdoor',           'ru' => 'Активный отдых',        'en' => 'Outdoor'],
    ['code' => 'books',          'score' => 4, 'ro' => 'Cărți',             'ru' => 'Книги',                 'en' => 'Books'],
    ['code' => 'board_games',    'score' => 5, 'ro' => 'Jocuri de societate','ru' => 'Настольные игры',      'en' => 'Board games'],
    ['code' => 'toys',           'score' => 5, 'ro' => 'Jucării',           'ru' => 'Игрушки',               'en' => 'Toys'],
    ['code' => 'wine_spirits',   'score' => 4, 'ro' => 'Vinuri și băuturi', 'ru' => 'Вино и напитки',        'en' => 'Wine & spirits'],
    ['code' => 'auto_care',      'score' => 3, 'ro' => 'Îngrijire auto',    'ru' => 'Уход за авто',          'en' => 'Car care'],
    ['code' => 'tools',          'score' => 3, 'ro' => 'Scule',             'ru' => 'Инструменты',           'en' => 'Tools'],

    // Scor 1: nu ajung niciodată în recomandări, dar rămân căutabile.
    ['code' => 'consumables',    'score' => 1, 'ro' => 'Consumabile',       'ru' => 'Расходники',            'en' => 'Consumables'],
    ['code' => 'spare_parts',    'score' => 1, 'ro' => 'Piese de schimb',   'ru' => 'Запчасти',              'en' => 'Spare parts'],
];
