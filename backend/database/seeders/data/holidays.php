<?php

/**
 * Sărbătorile cu relevanță pentru cadouri, în Moldova.
 *
 * Nu e un calendar complet de zile libere — sunt doar ocaziile la care oamenii
 * chiar cumpără ceva. 8 Martie e cea mai importantă: cerere masivă, buget mic,
 * decizie rapidă (docs/11 § 7).
 *
 * ⚠️ De verificat datele cu un calendar oficial înainte de lansare, la fel ca
 * onomasticile (docs/15 § 4).
 */

return [
    [
        'code' => 'new_year', 'rule' => 'fixed', 'month' => 1, 'day' => 1, 'audience' => 'all',
        'ro'   => 'Anul Nou', 'ru' => 'Новый год', 'en' => 'New Year',
    ],
    [
        'code' => 'christmas_new', 'rule' => 'fixed', 'month' => 12, 'day' => 25, 'audience' => 'all',
        'ro'   => 'Crăciunul (stil nou)', 'ru' => 'Рождество (новый стиль)', 'en' => 'Christmas (new style)',
    ],
    [
        // În Moldova se sărbătoresc ambele date; utilizatorul le poate opri
        // pe cea care nu-l privește.
        'code' => 'christmas_old', 'rule' => 'fixed', 'month' => 1, 'day' => 7, 'audience' => 'all',
        'ro'   => 'Crăciunul (stil vechi)', 'ru' => 'Рождество (старый стиль)', 'en' => 'Christmas (old style)',
    ],
    [
        'code' => 'valentines', 'rule' => 'fixed', 'month' => 2, 'day' => 14, 'audience' => 'partner',
        'ro'   => 'Ziua Îndrăgostiților', 'ru' => 'День святого Валентина', 'en' => "Valentine's Day",
    ],
    [
        'code'          => 'march_8', 'rule' => 'fixed', 'month' => 3, 'day' => 8, 'audience' => 'women',
        'ro'            => 'Ziua Femeii', 'ru' => 'Международный женский день', 'en' => "International Women's Day",
        'reminder_days' => [14, 7, 3, 1],
    ],
    [
        'code' => 'easter', 'rule' => 'easter', 'offset' => 0, 'audience' => 'all',
        'ro'   => 'Paștele', 'ru' => 'Пасха', 'en' => 'Easter',
    ],
    [
        // Paștele Blajinilor — a doua zi de luni după Paște.
        'code' => 'memorial_easter', 'rule' => 'easter', 'offset' => 8, 'audience' => 'all',
        'ro'   => 'Paștele Blajinilor', 'ru' => 'Радоница', 'en' => 'Memorial Easter',
    ],
    [
        'code' => 'june_1', 'rule' => 'fixed', 'month' => 6, 'day' => 1, 'audience' => 'children',
        'ro'   => 'Ziua Copilului', 'ru' => 'День защиты детей', 'en' => "Children's Day",
    ],
    [
        'code' => 'september_1', 'rule' => 'fixed', 'month' => 9, 'day' => 1, 'audience' => 'children',
        'ro'   => 'Prima zi de școală', 'ru' => 'Первое сентября', 'en' => 'First day of school',
    ],
];
