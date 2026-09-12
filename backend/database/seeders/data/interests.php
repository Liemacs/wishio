<?php

/**
 * Taxonomia de interese — contractul dintre AI si catalog.
 *
 * AI-ul poate returna DOAR codurile de aici. Orice altceva se arunca la
 * validare, nu se "repara". Vezi docs/05-arhitectura.md § 3.
 *
 * Cuvintele-cheie servesc la doua lucruri:
 *   1. text liber despre o persoana -> interese (ecranul P5; si Faza 0, fara AI)
 *   2. titlu de produs -> interese (maparea catalogului, docs/05 § 4)
 * Se scriu cu diacritice; normalizarea la potrivire le elimina.
 *
 * 'price' este banda tipica in MDL, orientativa. 'g' este afinitatea de gen:
 * indiciu slab pentru ranking, NICIODATA filtru.
 */
$i = fn (string $code, string $ro, string $ru, string $en, array $kw, ?array $price = null, ?string $g = null, bool $exp = false) => [
    'code' => $code, 'ro' => $ro, 'ru' => $ru, 'en' => $en,
    'kw'   => $kw, 'price' => $price, 'gender' => $g, 'experience' => $exp,
];

return [

    // ─────────────────────────────────────────────────────────── TEHNOLOGIE
    [
        'code'      => 'tech', 'icon' => '💻',
        'ro'        => 'Tehnologie', 'ru' => 'Технологии', 'en' => 'Technology',
        'interests' => [
            $i('audio', 'Audio și căști', 'Аудио и наушники', 'Audio & headphones', [
                'ro' => ['căști', 'boxă', 'difuzor', 'audio', 'sunet', 'muzică', 'airpods', 'hi-fi', 'soundbar'],
                'ru' => ['наушники', 'колонка', 'аудио', 'звук', 'музыка', 'аирподс', 'саундбар'],
                'en' => ['headphones', 'earbuds', 'speaker', 'audio', 'sound', 'soundbar'],
            ], [500, 5000]),
            $i('computing', 'Calculatoare și periferice', 'Компьютеры и периферия', 'Computers & peripherals', [
                'ro' => ['laptop', 'calculator', 'tastatură', 'mouse', 'monitor', 'ssd', 'pc', 'programator', 'it'],
                'ru' => ['ноутбук', 'компьютер', 'клавиатура', 'мышь', 'монитор', 'программист', 'айти'],
                'en' => ['laptop', 'computer', 'keyboard', 'mouse', 'monitor', 'developer', 'programmer'],
            ], [800, 15000]),
            $i('mobile_accessories', 'Accesorii telefon', 'Аксессуары для телефона', 'Phone accessories', [
                'ro' => ['telefon', 'husă', 'încărcător', 'powerbank', 'suport telefon', 'folie'],
                'ru' => ['телефон', 'чехол', 'зарядка', 'повербанк', 'держатель'],
                'en' => ['phone', 'case', 'charger', 'powerbank', 'phone holder'],
            ], [200, 2000]),
            $i('photo_video', 'Foto și video', 'Фото и видео', 'Photo & video', [
                'ro' => ['fotografie', 'aparat foto', 'cameră', 'obiectiv', 'trepied', 'gopro', 'filmare', 'poze'],
                'ru' => ['фотография', 'фотоаппарат', 'камера', 'объектив', 'штатив', 'гопро', 'съёмка'],
                'en' => ['photography', 'camera', 'lens', 'tripod', 'gopro', 'filming'],
            ], [1000, 20000]),
            $i('smart_home', 'Casă inteligentă', 'Умный дом', 'Smart home', [
                'ro' => ['smart home', 'bec inteligent', 'aspirator robot', 'alexa', 'automatizare casă'],
                'ru' => ['умный дом', 'умная лампа', 'робот пылесос', 'алекса', 'автоматизация'],
                'en' => ['smart home', 'smart bulb', 'robot vacuum', 'alexa', 'home automation'],
            ], [500, 8000]),
            $i('wearables', 'Ceasuri smart și brățări', 'Смарт-часы и браслеты', 'Smartwatches & trackers', [
                'ro' => ['smartwatch', 'ceas smart', 'brățară fitness', 'garmin', 'apple watch', 'tracker'],
                'ru' => ['смарт часы', 'фитнес браслет', 'гармин', 'эпл вотч', 'трекер'],
                'en' => ['smartwatch', 'fitness tracker', 'garmin', 'apple watch'],
            ], [800, 8000]),
            $i('drones', 'Drone', 'Дроны', 'Drones', [
                'ro' => ['dronă', 'drona', 'dji', 'quadcopter'],
                'ru' => ['дрон', 'квадрокоптер', 'дижиай'],
                'en' => ['drone', 'quadcopter', 'dji'],
            ], [2000, 25000]),
        ],
    ],

    // ───────────────────────────────────────────────────────────────── AUTO
    [
        'code'      => 'auto', 'icon' => '🚗',
        'ro'        => 'Auto', 'ru' => 'Авто', 'en' => 'Auto',
        'interests' => [
            $i('car_care', 'Îngrijire auto', 'Уход за автомобилем', 'Car care', [
                'ro' => ['detailing', 'spălătorie', 'ceară auto', 'polish', 'aspirator auto', 'îngrijire mașină'],
                'ru' => ['детейлинг', 'мойка', 'полироль', 'уход за авто', 'автопылесос'],
                'en' => ['detailing', 'car wash', 'polish', 'car care'],
            ], [300, 3000], 'm'),
            $i('car_accessories', 'Accesorii auto', 'Автоаксессуары', 'Car accessories', [
                'ro' => ['mașini', 'mașină', 'auto', 'huse scaune', 'covorașe', 'cameră bord', 'suport auto', 'șofer'],
                'ru' => ['машина', 'авто', 'чехлы', 'коврики', 'видеорегистратор', 'водитель'],
                'en' => ['car', 'auto', 'seat covers', 'dashcam', 'driver'],
            ], [200, 5000], 'm'),
            $i('motorsport', 'Motorsport', 'Автоспорт', 'Motorsport', [
                'ro' => ['karting', 'curse', 'formula 1', 'drift', 'raliu', 'motorsport'],
                'ru' => ['картинг', 'гонки', 'формула 1', 'дрифт', 'ралли', 'автоспорт'],
                'en' => ['karting', 'racing', 'formula 1', 'drift', 'rally', 'motorsport'],
            ], [500, 4000], 'm'),
        ],
    ],

    // ──────────────────────────────────────────────────────────────── SPORT
    [
        'code'      => 'sport', 'icon' => '🏋️',
        'ro'        => 'Sport', 'ru' => 'Спорт', 'en' => 'Sports',
        'interests' => [
            $i('fitness_gym', 'Fitness și sală', 'Фитнес и зал', 'Fitness & gym', [
                'ro' => ['sală', 'fitness', 'gantere', 'antrenament', 'proteine', 'culturism', 'sport'],
                'ru' => ['зал', 'фитнес', 'гантели', 'тренировка', 'протеин', 'качалка'],
                'en' => ['gym', 'fitness', 'dumbbells', 'workout', 'protein'],
            ], [300, 4000]),
            $i('running', 'Alergare', 'Бег', 'Running', [
                'ro' => ['alergare', 'maraton', 'jogging', 'adidași alergare', 'cros'],
                'ru' => ['бег', 'марафон', 'джоггинг', 'кроссовки для бега'],
                'en' => ['running', 'marathon', 'jogging', 'running shoes'],
            ], [400, 4000]),
            $i('cycling', 'Ciclism', 'Велоспорт', 'Cycling', [
                'ro' => ['bicicletă', 'ciclism', 'bike', 'cască bicicletă', 'mtb'],
                'ru' => ['велосипед', 'велоспорт', 'байк', 'шлем', 'мтб'],
                'en' => ['bicycle', 'cycling', 'bike', 'helmet', 'mtb'],
            ], [500, 15000]),
            $i('outdoor_hiking', 'Drumeții și natură', 'Походы и природа', 'Hiking & outdoors', [
                'ro' => ['drumeții', 'munte', 'trekking', 'natură', 'rucsac', 'excursii', 'hiking'],
                'ru' => ['походы', 'горы', 'треккинг', 'природа', 'рюкзак'],
                'en' => ['hiking', 'mountains', 'trekking', 'outdoors', 'backpack'],
            ], [400, 5000]),
            $i('team_sports', 'Sporturi de echipă', 'Командные виды спорта', 'Team sports', [
                'ro' => ['fotbal', 'baschet', 'volei', 'handbal', 'minge', 'echipă'],
                'ru' => ['футбол', 'баскетбол', 'волейбол', 'гандбол', 'мяч'],
                'en' => ['football', 'soccer', 'basketball', 'volleyball', 'ball'],
            ], [200, 2500]),
            $i('winter_sports', 'Sporturi de iarnă', 'Зимние виды спорта', 'Winter sports', [
                'ro' => ['schi', 'snowboard', 'patinaj', 'iarnă', 'zăpadă'],
                'ru' => ['лыжи', 'сноуборд', 'коньки', 'зима'],
                'en' => ['ski', 'snowboard', 'skating', 'winter'],
            ], [800, 10000]),
            $i('water_sports', 'Sporturi acvatice', 'Водные виды спорта', 'Water sports', [
                'ro' => ['înot', 'piscină', 'sup', 'caiac', 'scufundări', 'surf'],
                'ru' => ['плавание', 'бассейн', 'сап', 'каяк', 'дайвинг', 'сёрф'],
                'en' => ['swimming', 'pool', 'sup', 'kayak', 'diving', 'surf'],
            ], [400, 8000]),
        ],
    ],

    // ─────────────────────────────────────────────────────────────── GAMING
    [
        'code'      => 'gaming', 'icon' => '🎮',
        'ro'        => 'Gaming', 'ru' => 'Игры', 'en' => 'Gaming',
        'interests' => [
            $i('console_gaming', 'Console', 'Консоли', 'Consoles', [
                'ro' => ['playstation', 'ps5', 'xbox', 'nintendo', 'switch', 'consolă', 'joc video'],
                'ru' => ['плейстейшн', 'иксбокс', 'нинтендо', 'консоль', 'приставка'],
                'en' => ['playstation', 'xbox', 'nintendo', 'console', 'video game'],
            ], [500, 15000]),
            $i('pc_gaming', 'PC gaming', 'ПК-гейминг', 'PC gaming', [
                'ro' => ['gaming', 'mouse gaming', 'tastatură mecanică', 'scaun gaming', 'placă video', 'steam'],
                'ru' => ['гейминг', 'игровая мышь', 'механическая клавиатура', 'игровое кресло', 'видеокарта'],
                'en' => ['gaming', 'gaming mouse', 'mechanical keyboard', 'gaming chair', 'gpu'],
            ], [500, 12000]),
            $i('board_games', 'Jocuri de societate', 'Настольные игры', 'Board games', [
                'ro' => ['joc de societate', 'board game', 'puzzle', 'cărți de joc', 'șah', 'monopoly'],
                'ru' => ['настольная игра', 'пазл', 'шахматы', 'монополия'],
                'en' => ['board game', 'puzzle', 'chess', 'monopoly', 'card game'],
            ], [200, 1500]),
        ],
    ],

    // ─────────────────────────────────────────────────────────── FRUMUSEȚE
    [
        'code'      => 'beauty', 'icon' => '💄',
        'ro'        => 'Frumusețe', 'ru' => 'Красота', 'en' => 'Beauty',
        'interests' => [
            $i('fragrance_women', 'Parfumuri damă', 'Женская парфюмерия', "Women's fragrance", [
                'ro' => ['parfum', 'parfum damă', 'apă de parfum', 'aromă'],
                'ru' => ['духи', 'парфюм', 'женский парфюм', 'аромат'],
                'en' => ['perfume', 'fragrance', 'eau de parfum'],
            ], [400, 4000], 'f'),
            $i('fragrance_men', 'Parfumuri bărbați', 'Мужская парфюмерия', "Men's fragrance", [
                'ro' => ['parfum bărbați', 'apă de toaletă', 'after shave'],
                'ru' => ['мужской парфюм', 'туалетная вода', 'афтершейв'],
                'en' => ['mens fragrance', 'cologne', 'aftershave'],
            ], [400, 4000], 'm'),
            $i('skincare', 'Îngrijirea pielii', 'Уход за кожей', 'Skincare', [
                'ro' => ['cremă', 'ser', 'îngrijire ten', 'mască față', 'skincare'],
                'ru' => ['крем', 'сыворотка', 'уход за кожей', 'маска для лица'],
                'en' => ['cream', 'serum', 'skincare', 'face mask'],
            ], [200, 2500]),
            $i('makeup', 'Machiaj', 'Макияж', 'Makeup', [
                'ro' => ['machiaj', 'ruj', 'fard', 'rimel', 'paletă', 'pensule machiaj'],
                'ru' => ['макияж', 'помада', 'тени', 'тушь', 'палетка', 'кисти'],
                'en' => ['makeup', 'lipstick', 'eyeshadow', 'mascara', 'palette'],
            ], [200, 2500], 'f'),
            $i('hair_care', 'Îngrijirea părului', 'Уход за волосами', 'Hair care', [
                'ro' => ['păr', 'uscător', 'placă de păr', 'șampon', 'ondulator', 'coafură'],
                'ru' => ['волосы', 'фен', 'утюжок', 'шампунь', 'плойка'],
                'en' => ['hair', 'hairdryer', 'straightener', 'shampoo', 'curler'],
            ], [300, 4000]),
            $i('grooming_men', 'Îngrijire bărbați', 'Мужской уход', "Men's grooming", [
                'ro' => ['barbă', 'aparat de ras', 'trimmer', 'bărbierit', 'ulei de barbă'],
                'ru' => ['борода', 'бритва', 'триммер', 'бритьё', 'масло для бороды'],
                'en' => ['beard', 'razor', 'trimmer', 'shaving', 'beard oil'],
            ], [300, 3000], 'm'),
        ],
    ],

    // ───────────────────────────────────────────────────────────────── MODĂ
    [
        'code'      => 'fashion', 'icon' => '👜',
        'ro'        => 'Modă', 'ru' => 'Мода', 'en' => 'Fashion',
        'interests' => [
            $i('watches', 'Ceasuri', 'Часы', 'Watches', [
                'ro' => ['ceas', 'ceas de mână', 'curea ceas'],
                'ru' => ['часы', 'наручные часы', 'ремешок'],
                'en' => ['watch', 'wristwatch', 'watch strap'],
            ], [500, 10000]),
            $i('jewelry', 'Bijuterii', 'Украшения', 'Jewelry', [
                'ro' => ['bijuterii', 'inel', 'cercei', 'colier', 'brățară', 'lănțișor', 'aur', 'argint'],
                'ru' => ['украшения', 'кольцо', 'серьги', 'колье', 'браслет', 'цепочка', 'золото', 'серебро'],
                'en' => ['jewelry', 'ring', 'earrings', 'necklace', 'bracelet', 'gold', 'silver'],
            ], [300, 8000], 'f'),
            $i('bags', 'Genți și rucsacuri', 'Сумки и рюкзаки', 'Bags & backpacks', [
                'ro' => ['geantă', 'rucsac', 'poșetă', 'portofel', 'borsetă'],
                'ru' => ['сумка', 'рюкзак', 'кошелёк', 'барсетка'],
                'en' => ['bag', 'backpack', 'purse', 'wallet'],
            ], [300, 5000]),
            $i('footwear', 'Încălțăminte', 'Обувь', 'Footwear', [
                'ro' => ['pantofi', 'adidași', 'ghete', 'cizme', 'sneakers', 'încălțăminte'],
                'ru' => ['обувь', 'кроссовки', 'ботинки', 'сапоги', 'туфли'],
                'en' => ['shoes', 'sneakers', 'boots', 'footwear'],
            ], [500, 5000]),
            $i('apparel', 'Îmbrăcăminte', 'Одежда', 'Apparel', [
                'ro' => ['haine', 'tricou', 'hanorac', 'geacă', 'rochie', 'cămașă', 'îmbrăcăminte'],
                'ru' => ['одежда', 'худи', 'куртка', 'платье', 'рубашка', 'свитер'],
                'en' => ['clothes', 'tshirt', 'hoodie', 'jacket', 'dress', 'shirt'],
            ], [300, 4000]),
            $i('accessories_fashion', 'Accesorii', 'Аксессуары', 'Accessories', [
                'ro' => ['ochelari de soare', 'curea', 'fular', 'eșarfă', 'pălărie', 'mănuși'],
                'ru' => ['очки', 'ремень', 'шарф', 'шапка', 'перчатки'],
                'en' => ['sunglasses', 'belt', 'scarf', 'hat', 'gloves'],
            ], [200, 2500]),
        ],
    ],

    // ────────────────────────────────────────────────────────────────── CASĂ
    [
        'code'      => 'home', 'icon' => '🏠',
        'ro'        => 'Casă', 'ru' => 'Дом', 'en' => 'Home',
        'interests' => [
            $i('kitchen_gadgets', 'Aparate de bucătărie', 'Кухонная техника', 'Kitchen appliances', [
                'ro' => ['blender', 'mixer', 'friteuză', 'airfryer', 'robot bucătărie', 'gătit', 'gătește', 'gătească', 'gătesc', 'bucătărie', 'bucătar'],
                'ru' => ['блендер', 'миксер', 'фритюрница', 'кухонный комбайн', 'готовка', 'кухня'],
                'en' => ['blender', 'mixer', 'airfryer', 'food processor', 'cooking', 'kitchen'],
            ], [500, 6000]),
            $i('cookware', 'Vase și ustensile', 'Посуда и утварь', 'Cookware & utensils', [
                'ro' => ['tigaie', 'oală', 'set cuțite', 'vase', 'tacâmuri', 'farfurii'],
                'ru' => ['сковорода', 'кастрюля', 'ножи', 'посуда', 'тарелки'],
                'en' => ['pan', 'pot', 'knives', 'cookware', 'plates'],
            ], [300, 4000]),
            $i('coffee_gear', 'Cafea — echipament', 'Кофейное оборудование', 'Coffee gear', [
                'ro' => ['espressor', 'cafetieră', 'râșniță', 'aparat cafea', 'french press'],
                'ru' => ['кофемашина', 'кофеварка', 'кофемолка', 'френч-пресс'],
                'en' => ['espresso machine', 'coffee maker', 'grinder', 'french press'],
            ], [800, 12000]),
            $i('home_decor', 'Decorațiuni', 'Декор', 'Home decor', [
                'ro' => ['decor', 'lumânări', 'vază', 'tablou', 'ramă foto', 'lampă'],
                'ru' => ['декор', 'свечи', 'ваза', 'картина', 'рамка', 'лампа'],
                'en' => ['decor', 'candles', 'vase', 'painting', 'photo frame', 'lamp'],
            ], [150, 2500]),
            $i('home_textiles', 'Textile de casă', 'Домашний текстиль', 'Home textiles', [
                'ro' => ['lenjerie de pat', 'pled', 'prosoape', 'pernă', 'halat'],
                'ru' => ['постельное бельё', 'плед', 'полотенца', 'подушка', 'халат'],
                'en' => ['bed linen', 'blanket', 'towels', 'pillow', 'robe'],
            ], [200, 3000]),
            $i('plants', 'Plante și flori', 'Растения и цветы', 'Plants & flowers', [
                'ro' => ['plante', 'flori', 'ghiveci', 'buchet', 'orhidee', 'suculente'],
                'ru' => ['растения', 'цветы', 'горшок', 'букет', 'орхидея', 'суккуленты'],
                'en' => ['plants', 'flowers', 'pot', 'bouquet', 'orchid', 'succulents'],
            ], [150, 1500]),
            $i('tools_diy', 'Scule și bricolaj', 'Инструменты и DIY', 'Tools & DIY', [
                'ro' => ['scule', 'bormașină', 'trusă scule', 'bricolaj', 'șurubelniță', 'meșterit'],
                'ru' => ['инструменты', 'дрель', 'набор инструментов', 'отвёртка', 'мастерить'],
                'en' => ['tools', 'drill', 'toolkit', 'screwdriver', 'diy'],
            ], [300, 5000], 'm'),
            $i('bbq_grill', 'Grătar și BBQ', 'Гриль и барбекю', 'Grill & BBQ', [
                'ro' => ['grătar', 'bbq', 'afumătoare', 'frigărui', 'mangal'],
                'ru' => ['гриль', 'барбекю', 'мангал', 'шашлык', 'коптильня'],
                'en' => ['grill', 'bbq', 'smoker', 'skewers'],
            ], [400, 6000], 'm'),
        ],
    ],

    // ────────────────────────────────────────────────────────────── CULTURĂ
    [
        'code'      => 'culture', 'icon' => '📚',
        'ro'        => 'Cultură', 'ru' => 'Культура', 'en' => 'Culture',
        'interests' => [
            $i('books_fiction', 'Cărți — ficțiune', 'Книги — художественные', 'Books — fiction', [
                'ro' => ['cărți', 'carte', 'roman', 'citit', 'citește', 'citesc', 'cititor', 'literatură', 'ficțiune', 'lectură'],
                'ru' => ['книги', 'книга', 'роман', 'чтение', 'литература'],
                'en' => ['books', 'novel', 'reading', 'literature', 'fiction'],
            ], [100, 800]),
            $i('books_nonfiction', 'Cărți — non-ficțiune', 'Книги — нон-фикшн', 'Books — non-fiction', [
                'ro' => ['business', 'dezvoltare personală', 'psihologie', 'istorie', 'biografie'],
                'ru' => ['бизнес', 'саморазвитие', 'психология', 'история', 'биография'],
                'en' => ['business', 'self-help', 'psychology', 'history', 'biography'],
            ], [150, 900]),
            $i('music_instruments', 'Instrumente muzicale', 'Музыкальные инструменты', 'Musical instruments', [
                'ro' => ['chitară', 'pian', 'vioară', 'muzician', 'cântă la', 'instrument'],
                'ru' => ['гитара', 'пианино', 'скрипка', 'музыкант', 'играет на'],
                'en' => ['guitar', 'piano', 'violin', 'musician', 'instrument'],
            ], [800, 15000]),
            $i('vinyl_music', 'Muzică și viniluri', 'Музыка и винил', 'Music & vinyl', [
                'ro' => ['vinil', 'pickup', 'disc', 'colecție muzică', 'melomanie'],
                'ru' => ['винил', 'проигрыватель', 'пластинка', 'коллекция музыки'],
                'en' => ['vinyl', 'turntable', 'record', 'music collection'],
            ], [300, 6000]),
            $i('art_supplies', 'Artă și desen', 'Искусство и рисование', 'Art & drawing', [
                'ro' => ['pictură', 'desen', 'acuarele', 'șevalet', 'creioane', 'artă'],
                'ru' => ['живопись', 'рисование', 'акварель', 'мольберт', 'карандаши'],
                'en' => ['painting', 'drawing', 'watercolor', 'easel', 'art'],
            ], [200, 3000]),
            $i('cinema_home', 'Filme și seriale', 'Кино и сериалы', 'Movies & series', [
                'ro' => ['filme', 'seriale', 'cinema', 'netflix', 'proiector'],
                'ru' => ['фильмы', 'сериалы', 'кино', 'нетфликс', 'проектор'],
                'en' => ['movies', 'series', 'cinema', 'netflix', 'projector'],
            ], [200, 8000]),
        ],
    ],

    // ──────────────────────────────────────────────────────── MÂNCARE & BĂUTURĂ
    [
        'code'      => 'food_drink', 'icon' => '☕',
        'ro'        => 'Mâncare și băutură', 'ru' => 'Еда и напитки', 'en' => 'Food & drink',
        'interests' => [
            $i('coffee', 'Cafea', 'Кофе', 'Coffee', [
                'ro' => ['cafea', 'espresso', 'cappuccino', 'boabe cafea', 'cafeină'],
                'ru' => ['кофе', 'эспрессо', 'капучино', 'зёрна'],
                'en' => ['coffee', 'espresso', 'cappuccino', 'beans'],
            ], [150, 1500]),
            $i('tea', 'Ceai', 'Чай', 'Tea', [
                'ro' => ['ceai', 'ceainic', 'infuzie', 'matcha'],
                'ru' => ['чай', 'чайник', 'матча'],
                'en' => ['tea', 'teapot', 'matcha'],
            ], [150, 1500]),
            $i('wine', 'Vin', 'Вино', 'Wine', [
                'ro' => ['vin', 'vinuri', 'degustare', 'somelier', 'cramă', 'pahare vin'],
                'ru' => ['вино', 'дегустация', 'сомелье', 'винодельня', 'бокалы'],
                'en' => ['wine', 'tasting', 'sommelier', 'winery', 'wine glasses'],
            ], [200, 3000]),
            $i('spirits', 'Băuturi tari', 'Крепкие напитки', 'Spirits', [
                'ro' => ['whisky', 'coniac', 'rom', 'gin', 'lichior', 'divin'],
                'ru' => ['виски', 'коньяк', 'ром', 'джин', 'ликёр'],
                'en' => ['whisky', 'cognac', 'rum', 'gin', 'liqueur'],
            ], [400, 5000], 'm'),
            $i('sweets', 'Dulciuri', 'Сладости', 'Sweets', [
                'ro' => ['ciocolată', 'bomboane', 'dulciuri', 'praline', 'tort', 'prăjituri'],
                'ru' => ['шоколад', 'конфеты', 'сладости', 'торт', 'пирожные'],
                'en' => ['chocolate', 'candy', 'sweets', 'cake', 'pastries'],
            ], [100, 1200]),
            $i('gourmet_food', 'Delicatese', 'Деликатесы', 'Gourmet food', [
                'ro' => ['delicatese', 'brânzeturi', 'coș cadou', 'mezeluri', 'trufe', 'gourmet'],
                'ru' => ['деликатесы', 'сыры', 'подарочная корзина', 'трюфели'],
                'en' => ['delicacies', 'cheese', 'gift basket', 'truffles', 'gourmet'],
            ], [300, 3000]),
        ],
    ],

    // ────────────────────────────────────────────────────────────── WELLNESS
    [
        'code'      => 'wellness', 'icon' => '🧘',
        'ro'        => 'Wellness', 'ru' => 'Велнес', 'en' => 'Wellness',
        'interests' => [
            $i('spa_products', 'Produse spa', 'Спа-товары', 'Spa products', [
                'ro' => ['spa', 'sare de baie', 'uleiuri', 'aromaterapie', 'relaxare'],
                'ru' => ['спа', 'соль для ванны', 'масла', 'ароматерапия', 'релакс'],
                'en' => ['spa', 'bath salt', 'oils', 'aromatherapy', 'relax'],
            ], [200, 2000]),
            $i('massage', 'Masaj', 'Массаж', 'Massage', [
                'ro' => ['masaj', 'aparat masaj', 'pistol masaj', 'perna masaj'],
                'ru' => ['массаж', 'массажёр', 'массажный пистолет'],
                'en' => ['massage', 'massager', 'massage gun'],
            ], [400, 4000]),
            $i('yoga', 'Yoga și meditație', 'Йога и медитация', 'Yoga & meditation', [
                'ro' => ['yoga', 'saltea yoga', 'meditație', 'pilates', 'mindfulness'],
                'ru' => ['йога', 'коврик', 'медитация', 'пилатес'],
                'en' => ['yoga', 'yoga mat', 'meditation', 'pilates'],
            ], [200, 2000], 'f'),
            $i('sauna', 'Saună și baie', 'Сауна и баня', 'Sauna', [
                'ro' => ['saună', 'baie', 'prosop saună', 'căldură'],
                'ru' => ['сауна', 'баня', 'банные принадлежности'],
                'en' => ['sauna', 'steam bath'],
            ], [200, 2000]),
        ],
    ],

    // ──────────────────────────────────────────────────────────────── CĂLĂTORII
    [
        'code'      => 'travel', 'icon' => '✈️',
        'ro'        => 'Călătorii', 'ru' => 'Путешествия', 'en' => 'Travel',
        'interests' => [
            $i('travel_gear', 'Echipament de voiaj', 'Товары для путешествий', 'Travel gear', [
                'ro' => ['călătorii', 'voiaj', 'adaptor priză', 'organizator bagaj', 'pernă gât'],
                'ru' => ['путешествия', 'переходник', 'органайзер', 'подушка для шеи'],
                'en' => ['travel', 'adapter', 'packing cubes', 'neck pillow'],
            ], [150, 1500]),
            $i('luggage', 'Bagaje', 'Багаж', 'Luggage', [
                'ro' => ['valiză', 'troler', 'geantă voiaj', 'bagaj'],
                'ru' => ['чемодан', 'сумка для путешествий', 'багаж'],
                'en' => ['suitcase', 'luggage', 'travel bag'],
            ], [800, 5000]),
            $i('camping', 'Camping', 'Кемпинг', 'Camping', [
                'ro' => ['cort', 'sac de dormit', 'camping', 'lanternă', 'primus'],
                'ru' => ['палатка', 'спальник', 'кемпинг', 'фонарь', 'горелка'],
                'en' => ['tent', 'sleeping bag', 'camping', 'lantern', 'stove'],
            ], [400, 5000]),
        ],
    ],

    // ─────────────────────────────────────────────────────────────────── COPII
    [
        'code'      => 'kids', 'icon' => '🧸',
        'ro'        => 'Copii', 'ru' => 'Дети', 'en' => 'Kids',
        'interests' => [
            $i('toys', 'Jucării', 'Игрушки', 'Toys', [
                'ro' => ['jucării', 'păpușă', 'mașinuță', 'plus', 'copil'],
                'ru' => ['игрушки', 'кукла', 'машинка', 'плюшевая', 'ребёнок'],
                'en' => ['toys', 'doll', 'toy car', 'plush', 'child'],
            ], [150, 1500]),
            $i('construction_toys', 'Jocuri de construcție', 'Конструкторы', 'Construction toys', [
                'ro' => ['lego', 'constructor', 'cuburi', 'magformers'],
                'ru' => ['лего', 'конструктор', 'кубики'],
                'en' => ['lego', 'building blocks', 'construction set'],
            ], [300, 4000]),
            $i('educational_toys', 'Jucării educative', 'Развивающие игрушки', 'Educational toys', [
                'ro' => ['educativ', 'științific', 'microscop', 'experimente', 'dezvoltare'],
                'ru' => ['развивающие', 'научный', 'микроскоп', 'эксперименты'],
                'en' => ['educational', 'science kit', 'microscope', 'experiments'],
            ], [200, 2000]),
            $i('baby_care', 'Bebeluși', 'Малыши', 'Baby', [
                'ro' => ['bebeluș', 'nou-născut', 'cărucior', 'scutece', 'botez', 'cumătrie'],
                'ru' => ['малыш', 'новорождённый', 'коляска', 'подгузники', 'крестины'],
                'en' => ['baby', 'newborn', 'stroller', 'diapers', 'christening'],
            ], [200, 8000]),
        ],
    ],

    // ─────────────────────────────────────────────────────────── ANIMALE
    [
        'code'      => 'pets', 'icon' => '🐕',
        'ro'        => 'Animale de companie', 'ru' => 'Домашние животные', 'en' => 'Pets',
        'interests' => [
            $i('dogs', 'Câini', 'Собаки', 'Dogs', [
                'ro' => ['câine', 'cățel', 'lesă', 'zgardă', 'jucărie câine'],
                'ru' => ['собака', 'щенок', 'поводок', 'ошейник'],
                'en' => ['dog', 'puppy', 'leash', 'collar'],
            ], [150, 2000]),
            $i('cats', 'Pisici', 'Кошки', 'Cats', [
                'ro' => ['pisică', 'pisic', 'ansă', 'litieră', 'zgârietoare'],
                'ru' => ['кошка', 'кот', 'лоток', 'когтеточка'],
                'en' => ['cat', 'kitten', 'litter box', 'scratching post'],
            ], [150, 2000]),
        ],
    ],

    // ─────────────────────────────────────────────────────────────── HOBBY-URI
    [
        'code'      => 'hobby', 'icon' => '🎣',
        'ro'        => 'Hobby-uri', 'ru' => 'Хобби', 'en' => 'Hobbies',
        'interests' => [
            $i('fishing', 'Pescuit', 'Рыбалка', 'Fishing', [
                'ro' => ['pescuit', 'undiță', 'momeală', 'pescar'],
                'ru' => ['рыбалка', 'удочка', 'приманка', 'рыбак'],
                'en' => ['fishing', 'rod', 'bait', 'angler'],
            ], [300, 5000], 'm'),
            $i('hunting', 'Vânătoare', 'Охота', 'Hunting', [
                'ro' => ['vânătoare', 'vânător', 'binoclu', 'cuțit vânătoare'],
                'ru' => ['охота', 'охотник', 'бинокль', 'нож'],
                'en' => ['hunting', 'hunter', 'binoculars', 'hunting knife'],
            ], [500, 8000], 'm'),
            $i('gardening', 'Grădinărit', 'Садоводство', 'Gardening', [
                'ro' => ['grădină', 'grădinărit', 'semințe', 'unelte grădină', 'seră'],
                'ru' => ['сад', 'садоводство', 'семена', 'садовые инструменты', 'теплица'],
                'en' => ['garden', 'gardening', 'seeds', 'garden tools'],
            ], [200, 3000]),
            $i('handmade', 'Handmade și cusut', 'Рукоделие', 'Handmade & crafts', [
                'ro' => ['croșetat', 'tricotat', 'cusut', 'handmade', 'mașină de cusut', 'broderie'],
                'ru' => ['вязание', 'шитьё', 'рукоделие', 'швейная машина', 'вышивка'],
                'en' => ['crochet', 'knitting', 'sewing', 'handmade', 'embroidery'],
            ], [200, 4000], 'f'),
            $i('collecting', 'Colecții', 'Коллекционирование', 'Collecting', [
                'ro' => ['colecție', 'monede', 'timbre', 'machete', 'figurine'],
                'ru' => ['коллекция', 'монеты', 'марки', 'модели', 'фигурки'],
                'en' => ['collection', 'coins', 'stamps', 'models', 'figurines'],
            ], [200, 4000]),
            $i('astronomy', 'Astronomie', 'Астрономия', 'Astronomy', [
                'ro' => ['telescop', 'astronomie', 'stele', 'spațiu'],
                'ru' => ['телескоп', 'астрономия', 'звёзды', 'космос'],
                'en' => ['telescope', 'astronomy', 'stars', 'space'],
            ], [800, 8000]),
        ],
    ],

    // ─────────────────────────────────────────────────────────── EXPERIENȚE
    // is_experience = true -> se cauta in Experience Engine (v1.1), nu in catalog.
    [
        'code'      => 'experiences', 'icon' => '🎉',
        'ro'        => 'Experiențe', 'ru' => 'Впечатления', 'en' => 'Experiences',
        'interests' => [
            $i('dining_out', 'Restaurante', 'Рестораны', 'Dining out', [
                'ro' => ['restaurant', 'cină', 'gastronomie', 'ieșit în oraș'],
                'ru' => ['ресторан', 'ужин', 'гастрономия'],
                'en' => ['restaurant', 'dinner', 'dining'],
            ], [400, 3000], null, true),
            $i('karting_exp', 'Karting', 'Картинг', 'Karting', [
                'ro' => ['karting', 'kart', 'curse'],
                'ru' => ['картинг', 'карт', 'гонки'],
                'en' => ['karting', 'go-kart'],
            ], [300, 2000], null, true),
            $i('escape_room', 'Escape room', 'Квест-комнаты', 'Escape room', [
                'ro' => ['escape room', 'quest', 'cameră de evadare'],
                'ru' => ['квест', 'квест-комната'],
                'en' => ['escape room', 'quest'],
            ], [400, 1500], null, true),
            $i('bowling_billiards', 'Bowling și biliard', 'Боулинг и бильярд', 'Bowling & billiards', [
                'ro' => ['bowling', 'biliard', 'popice'],
                'ru' => ['боулинг', 'бильярд'],
                'en' => ['bowling', 'billiards', 'pool'],
            ], [300, 1500], null, true),
            $i('concerts', 'Concerte și evenimente', 'Концерты и события', 'Concerts & events', [
                'ro' => ['concert', 'festival', 'teatru', 'spectacol', 'bilete'],
                'ru' => ['концерт', 'фестиваль', 'театр', 'спектакль', 'билеты'],
                'en' => ['concert', 'festival', 'theatre', 'show', 'tickets'],
            ], [200, 3000], null, true),
            $i('active_outdoor', 'Activități în aer liber', 'Активный отдых', 'Outdoor activities', [
                'ro' => ['paintball', 'atv', 'quad', 'tiroliană', 'parc aventura', 'rafting'],
                'ru' => ['пейнтбол', 'квадроцикл', 'верёвочный парк', 'рафтинг'],
                'en' => ['paintball', 'atv', 'quad', 'zipline', 'rafting'],
            ], [500, 3000], null, true),
            $i('wine_tasting', 'Degustări de vin', 'Дегустация вин', 'Wine tasting', [
                'ro' => ['degustare', 'cramă', 'tur vinărie', 'milestii mici', 'cricova'],
                'ru' => ['дегустация', 'винодельня', 'тур по подвалам', 'криково'],
                'en' => ['tasting', 'winery tour', 'cellar'],
            ], [400, 2500], null, true),
            $i('workshops', 'Ateliere și cursuri', 'Мастер-классы', 'Workshops & classes', [
                'ro' => ['atelier', 'master class', 'curs', 'ceramică', 'gătit împreună'],
                'ru' => ['мастер-класс', 'курс', 'керамика', 'кулинарный класс'],
                'en' => ['workshop', 'masterclass', 'course', 'pottery', 'cooking class'],
            ], [300, 2500], null, true),
            $i('photo_session', 'Ședințe foto', 'Фотосессии', 'Photo sessions', [
                'ro' => ['ședință foto', 'fotograf', 'sesiune foto'],
                'ru' => ['фотосессия', 'фотограф'],
                'en' => ['photo session', 'photographer'],
            ], [500, 3000], null, true),
        ],
    ],
];
