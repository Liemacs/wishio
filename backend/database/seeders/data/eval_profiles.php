<?php

/**
 * Setul de evaluare pentru motorul de recomandări (C8, docs/05 § 3).
 *
 * Rolul lui: să prindă regresiile. Când schimbi o pondere, un prompt sau
 * filtrul de catalog, rulezi asta și vezi imediat ce s-a stricat.
 *
 * ⚠️ Limită de știut: profilurile sunt plauzibile, nu reale. Cele 20 de
 * conversații din Faza 0 (docs/14) ar fi fost materialul potrivit — oamenii
 * nu scriu „interesat de audio”, scriu „ascultă muzică tot timpul”.
 * Până atunci, setul măsoară consecvența, nu calitatea absolută.
 *
 * Așteptări:
 *   min_results     — cel puțin atâtea rezultate
 *   max_results     — cel mult atâtea (0 = trebuie să nu întoarcă nimic)
 *   expect_any      — cel puțin un rezultat din aceste interese
 *   forbid_any      — niciun rezultat din aceste interese
 *   in_budget       — toate prețurile în intervalul cerut
 */
$c = fn (string $name, array $interests, ?int $min, ?int $max, array $expect = []) => array_merge([
    'name'        => $name, 'interests' => $interests, 'budget_min' => $min, 'budget_max' => $max,
    'min_results' => 1, 'in_budget' => true,
], $expect);

return [
    // ── Cazuri tipice ──────────────────────────────────────────────────────
    $c('programator, 28 ani, mașini și sală', ['computing', 'car_care', 'fitness_gym'], 500, 2000,
        ['expect_any' => ['computing', 'car_care', 'fitness_gym']]),
    $c('audiofil, buget mediu', ['audio'], 1000, 6000, ['expect_any' => ['audio']]),
    $c('bea multă cafea', ['coffee', 'coffee_gear'], 500, 5000, ['expect_any' => ['coffee_gear', 'coffee']]),
    $c('femeie, parfumuri și îngrijire', ['fragrance_women', 'skincare'], 500, 4000,
        ['expect_any' => ['fragrance_women', 'skincare']]),
    $c('bărbat, parfum', ['fragrance_men'], 1000, 3500, ['expect_any' => ['fragrance_men']]),
    $c('gătește mult', ['kitchen_gadgets', 'cookware'], 800, 3000,
        ['expect_any' => ['kitchen_gadgets', 'cookware']]),
    $c('copil, lego și jucării', ['construction_toys', 'toys'], 500, 4500,
        ['expect_any' => ['construction_toys', 'toys']]),
    $c('jocuri de societate', ['board_games'], 200, 1500, ['expect_any' => ['board_games']]),
    $c('drumeții și camping', ['camping', 'outdoor_hiking'], 500, 3000,
        ['expect_any' => ['camping', 'outdoor_hiking']]),
    $c('yoga și fitness', ['yoga', 'fitness_gym'], 400, 2500, ['expect_any' => ['yoga', 'fitness_gym']]),
    $c('gaming pe PC', ['pc_gaming'], 1000, 4000, ['expect_any' => ['pc_gaming']]),
    $c('console', ['console_gaming'], 800, 2000, ['expect_any' => ['console_gaming']]),
    $c('vin și băuturi', ['wine', 'spirits'], 400, 2000, ['expect_any' => ['wine', 'spirits']]),
    $c('ceasuri și bijuterii', ['watches', 'jewelry'], 500, 2000, ['expect_any' => ['watches', 'jewelry']]),
    $c('genți și rucsacuri', ['bags'], 300, 1500, ['expect_any' => ['bags']]),
    $c('decor și textile de casă', ['home_decor', 'home_textiles'], 200, 1500,
        ['expect_any' => ['home_decor', 'home_textiles']]),
    $c('fotografie și outdoor', ['photo_video', 'outdoor_hiking'], 2000, 12000,
        ['expect_any' => ['photo_video', 'outdoor_hiking']]),
    $c('accesorii auto', ['car_accessories', 'car_care'], 500, 2000,
        ['expect_any' => ['car_accessories', 'car_care']]),
    $c('ceas smart și fitness', ['wearables', 'fitness_gym'], 500, 8000,
        ['expect_any' => ['wearables', 'fitness_gym']]),
    $c('îngrijirea părului', ['hair_care'], 5000, 20000, ['expect_any' => ['hair_care']]),

    // ── Cazuri-limită: unde motoarele de recomandare se fac de râs ─────────
    $c('fără niciun interes cunoscut', [], 500, 2000,
        ['min_results' => 0, 'max_results' => 0]),
    $c('buget foarte mic, sub orice produs decent', ['audio'], 0, 100,
        ['min_results' => 0, 'max_results' => 0]),
    $c('buget uriaș', ['audio', 'computing'], 50000, 200000,
        ['min_results' => 0, 'max_results' => 0]),
    $c('interes fără produse în catalog', ['astronomy'], 500, 5000,
        ['min_results' => 0, 'max_results' => 0]),
    $c('interese multe, buget îngust', ['audio', 'coffee', 'fitness_gym', 'board_games', 'bags'], 700, 1000,
        ['expect_any' => ['audio', 'coffee_gear', 'fitness_gym', 'board_games', 'bags']]),
    $c('doar limita de jos a bugetului', ['audio'], 4000, null, ['expect_any' => ['audio']]),
    $c('doar limita de sus a bugetului', ['board_games'], null, 900, ['expect_any' => ['board_games']]),
    $c('fără buget declarat', ['coffee_gear'], null, null, ['expect_any' => ['coffee_gear', 'coffee']]),

    // ── Excluderi: un cadou nepotrivit strică mai mult decât ajută zece bune
    $c('îi place audio, dar evită parfumurile', ['audio', 'fragrance_men'], 500, 6000,
        ['avoid' => ['fragrance_men'], 'forbid_any' => ['fragrance_men']]),
    $c('totul e exclus', ['audio'], 500, 6000,
        ['avoid' => ['audio'], 'min_results' => 0, 'max_results' => 0]),
];
