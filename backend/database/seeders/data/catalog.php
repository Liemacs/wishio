<?php

/**
 * Catalog seed pentru dezvoltare.
 *
 * Nu e catalogul real — acela vine prin API-ul Magaziner (docs/12). Rolul lui
 * e să exerseze toate căile pipeline-ului: deduplicarea aceluiași produs la
 * mai multe magazine, filtrul de „nu poate fi cadou”, benzile de preț și
 * maparea pe taxonomia de interese.
 *
 * Prețurile sunt în MDL și orientative.
 */
$p = fn (string $merchant, string $id, string $title, float $price, string $category, array $interests, ?string $brand = null, ?float $old = null) => [
    'merchant' => $merchant, 'external_id' => $id, 'title' => $title, 'price' => $price,
    'category' => $category, 'interests' => $interests, 'brand' => $brand, 'old_price' => $old,
    'deeplink' => "https://example.md/$merchant/$id",
];

return [
    // ── Același produs la trei magazine: testul deduplicării ────────────────
    $p('darwin', 'd-1001', 'Căști Sony WH-1000XM5, negru', 5490, 'audio', ['audio'], 'Sony'),
    $p('bomba', 'b-2001', 'Sony WH-1000XM5 Black Wireless', 5299, 'audio', ['audio'], 'Sony'),
    $p('enter', 'e-3001', 'SONY WH-1000XM5 (negru) casti bluetooth', 5650, 'audio', ['audio'], 'Sony', 5900.0),

    $p('darwin', 'd-1002', 'Apple AirPods Pro 2', 4290, 'audio', ['audio'], 'Apple'),
    $p('bomba', 'b-2002', 'AirPods Pro 2 Apple, alb', 4150, 'audio', ['audio'], 'Apple'),

    $p('darwin', 'd-1003', 'Apple Watch SE 2', 6490, 'wearables', ['wearables'], 'Apple'),
    $p('enter', 'e-3003', 'Apple Watch SE 2 44mm', 6390, 'wearables', ['wearables'], 'Apple'),

    // ── Tehnologie ─────────────────────────────────────────────────────────
    $p('darwin', 'd-1010', 'Mouse Logitech MX Master 3S', 1890, 'computing', ['computing'], 'Logitech'),
    $p('bomba', 'b-2010', 'Logitech MX Master 3S wireless', 1790, 'computing', ['computing'], 'Logitech'),
    $p('enter', 'e-3011', 'Tastatură mecanică Keychron K2', 2290, 'computing', ['computing', 'pc_gaming'], 'Keychron'),
    $p('darwin', 'd-1012', 'Boxă portabilă JBL Flip 6', 1690, 'audio', ['audio'], 'JBL'),
    $p('bomba', 'b-2013', 'Xiaomi Mi Band 8', 690, 'wearables', ['wearables', 'fitness_gym'], 'Xiaomi'),
    $p('darwin', 'd-1014', 'Cameră GoPro HERO12', 8900, 'computing', ['photo_video', 'outdoor_hiking'], 'GoPro'),

    // ── Gaming ─────────────────────────────────────────────────────────────
    $p('enter', 'e-3020', 'Controller Sony DualSense PS5', 1290, 'gaming', ['console_gaming'], 'Sony'),
    $p('darwin', 'd-1021', 'Scaun gaming Trust GXT 708', 2890, 'gaming', ['pc_gaming'], 'Trust'),

    // ── Frumusețe ──────────────────────────────────────────────────────────
    $p('maximum', 'm-4001', 'Parfum Dior Sauvage EDT 100ml', 2890, 'fragrance', ['fragrance_men'], 'Dior'),
    $p('bomba', 'b-2030', 'Dior Sauvage 100 ml apă de toaletă', 2790, 'fragrance', ['fragrance_men'], 'Dior'),
    $p('maximum', 'm-4002', 'Parfum Chanel Coco Mademoiselle 50ml', 3490, 'fragrance', ['fragrance_women'], 'Chanel'),
    $p('maximum', 'm-4003', 'Set îngrijire ten The Ordinary', 890, 'cosmetics', ['skincare'], 'The Ordinary'),
    $p('maximum', 'm-4004', 'Uscător de păr Dyson Supersonic', 12900, 'cosmetics', ['hair_care'], 'Dyson'),

    // ── Modă ───────────────────────────────────────────────────────────────
    $p('maximum', 'm-4010', 'Lănțișor argint 925 cu pandantiv', 790, 'jewelry', ['jewelry'], null),
    $p('maximum', 'm-4011', 'Ceas Casio Vintage A168', 990, 'jewelry', ['watches'], 'Casio'),
    $p('enter', 'e-3030', 'Rucsac Xiaomi Mi Casual Daypack', 490, 'bags', ['bags'], 'Xiaomi'),
    $p('bomba', 'b-2040', 'Adidași Nike Air Force 1', 2890, 'footwear', ['footwear'], 'Nike'),

    // ── Casă și bucătărie ──────────────────────────────────────────────────
    $p('darwin', 'd-1040', 'Espressor DeLonghi Dedica EC685', 4290, 'coffee', ['coffee_gear', 'coffee'], 'DeLonghi'),
    $p('bomba', 'b-2050', 'Râșniță de cafea Hario Skerton', 890, 'coffee', ['coffee_gear', 'coffee'], 'Hario'),
    $p('darwin', 'd-1041', 'Blender Philips ProBlend 5000', 1590, 'kitchen', ['kitchen_gadgets'], 'Philips'),
    $p('enter', 'e-3040', 'Set cuțite Zwilling Gourmet 3 piese', 2190, 'kitchen', ['cookware'], 'Zwilling'),
    $p('maximum', 'm-4020', 'Lumânare parfumată Yankee Candle', 390, 'home_decor', ['home_decor'], 'Yankee Candle'),
    $p('maximum', 'm-4021', 'Set prosoape bumbac 100%', 590, 'home_decor', ['home_textiles'], null),

    // ── Sport ──────────────────────────────────────────────────────────────
    $p('darwin', 'd-1050', 'Saltea yoga Reebok 7mm', 690, 'fitness', ['yoga', 'fitness_gym'], 'Reebok'),
    $p('enter', 'e-3050', 'Gantere reglabile 2x10 kg', 1890, 'fitness', ['fitness_gym'], null),
    $p('darwin', 'd-1051', 'Cort Quechua 2 Seconds, 2 persoane', 2490, 'outdoor', ['camping', 'outdoor_hiking'], 'Quechua'),
    $p('enter', 'e-3051', 'Termos Stanley Classic 1L', 890, 'outdoor', ['outdoor_hiking', 'camping'], 'Stanley'),

    // ── Cultură și timp liber ──────────────────────────────────────────────
    $p('bomba', 'b-2060', 'Joc de societate Catan', 790, 'board_games', ['board_games'], 'Catan'),
    $p('bomba', 'b-2061', 'Puzzle Ravensburger 1000 piese', 290, 'board_games', ['board_games'], 'Ravensburger'),
    $p('enter', 'e-3060', 'LEGO Technic Bugatti', 3990, 'toys', ['construction_toys'], 'LEGO'),
    $p('enter', 'e-3061', 'LEGO City Statie de politie', 1290, 'toys', ['construction_toys', 'toys'], 'LEGO'),
    $p('bomba', 'b-2070', 'Set vinuri Cricova, 3 sticle', 1190, 'wine_spirits', ['wine'], 'Cricova'),
    $p('bomba', 'b-2071', 'Whisky Jameson 0.7L', 690, 'wine_spirits', ['spirits'], 'Jameson'),

    // ── Auto ───────────────────────────────────────────────────────────────
    $p('darwin', 'd-1060', 'Set detailing auto Meguiars', 990, 'auto_care', ['car_care'], 'Meguiars'),
    $p('darwin', 'd-1061', 'Aspirator auto Baseus A3', 890, 'auto_care', ['car_accessories', 'car_care'], 'Baseus'),

    // ── Ce NU trebuie să ajungă în recomandări ─────────────────────────────
    $p('darwin', 'd-9001', 'Cablu USB-C 2m Baseus', 90, 'consumables', [], 'Baseus'),
    $p('darwin', 'd-9002', 'Filtru de ulei Bosch P7023', 190, 'spare_parts', [], 'Bosch'),
    $p('bomba', 'b-9003', 'Cartuș toner HP 106A', 890, 'consumables', [], 'HP'),
    $p('enter', 'e-9004', 'Baterie auto Varta Blue 60Ah', 2490, 'spare_parts', [], 'Varta'),
    $p('darwin', 'd-9005', 'Sac de aspirator, set 5 buc', 120, 'consumables', [], null),
    $p('darwin', 'd-9006', 'Încărcător Samsung 25W', 290, 'consumables', [], 'Samsung'),
];
