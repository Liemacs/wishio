<?php

/**
 * Onomastici — calendarul ortodox, stil nou (Mitropolia Basarabiei / Patriarhia Romana).
 *
 * Varianta pe stil vechi (Mitropolia Moldovei / Patriarhia Moscovei) se GENEREAZA
 * automat de seeder: data civila = data pe stil nou + 13 zile.
 * Regula e verificabila: Sf. Nicolae 6 dec -> 19 dec; Sf. Gheorghe 23 apr -> 6 mai;
 * Sf. Tatiana 12 ian -> 25 ian («Татьянин день»).
 *
 * ⚠️ TOATE intrarile au is_verified = false pana la confruntarea cu un calendar
 * bisericesc tiparit. Onomasticile neverificate nu genereaza push — vezi
 * config('wishio.reminders.min_confidence_to_push') si docs/15-onomastici.md.
 *
 * Format alias: [nume, gen, confidence, is_diminutive, script]
 *   confidence: 1.00 forma exacta · 0.80 varianta · 0.60 diminutiv · 0.40 ambiguu
 */
$nm = fn (string $n, ?string $g = null, float $c = 1.0, bool $d = false, string $s = 'latin') => [
    'name' => $n, 'gender' => $g, 'confidence' => $c, 'diminutive' => $d, 'script' => $s,
];
$ru = fn (string $n, ?string $g = null, float $c = 1.0, bool $d = false) => [
    'name' => $n, 'gender' => $g, 'confidence' => $c, 'diminutive' => $d, 'script' => 'cyrillic',
];

return [
    [
        'code'  => 'sf_vasile', 'month' => 1, 'day' => 1, 'major' => true,
        'saint' => ['ro' => 'Sfântul Vasile cel Mare', 'ru' => 'Святой Василий Великий', 'en' => 'Saint Basil the Great'],
        'names' => [
            $nm('Vasile', 'm'), $nm('Vasilica', null, 0.8),
            $nm('Sile', 'm', 0.6, true), $nm('Lică', 'm', 0.4, true), $nm('Vasea', 'm', 0.6, true),
            $ru('Василий', 'm'), $ru('Вася', 'm', 0.6, true), $ru('Василиса', 'f', 0.8),
        ],
    ],
    [
        'code'  => 'boboteaza', 'month' => 1, 'day' => 6, 'major' => false,
        'saint' => ['ro' => 'Botezul Domnului (Boboteaza)', 'ru' => 'Крещение Господне', 'en' => 'Theophany'],
        'names' => [$nm('Iordan', 'm'), $nm('Iordana', 'f'), $ru('Богдан', 'm', 0.4)],
    ],
    [
        'code'  => 'sf_ioan', 'month' => 1, 'day' => 7, 'major' => true,
        'saint' => ['ro' => 'Soborul Sfântului Ioan Botezătorul', 'ru' => 'Собор Иоанна Предтечи', 'en' => 'Synaxis of Saint John the Baptist'],
        'names' => [
            $nm('Vania', 'm', 0.6, true),
            $nm('Ion', 'm'), $nm('Ioan', 'm'), $nm('Ioana', 'f'), $nm('Ionela', 'f', 0.8),
            $nm('Ionel', 'm', 0.6, true), $nm('Ionuț', 'm', 0.6, true), $nm('Nelu', 'm', 0.6, true),
            $nm('Nuțu', 'm', 0.4, true), $nm('Iancu', 'm', 0.8), $nm('Ivan', 'm', 0.8),
            $nm('Vanea', 'm', 0.6, true), $nm('Ivanna', 'f', 0.8),
            $ru('Иван', 'm'), $ru('Иоанн', 'm'), $ru('Ваня', 'm', 0.6, true), $ru('Иванна', 'f', 0.8),
        ],
    ],
    [
        'code'  => 'sf_tatiana', 'month' => 1, 'day' => 12, 'major' => true,
        'saint' => ['ro' => 'Sfânta Tatiana', 'ru' => 'Святая Татиана', 'en' => 'Saint Tatiana'],
        'names' => [
            $nm('Tanea', 'f', 0.6, true), $nm('Tatiana', 'f'), $nm('Tatiana', 'f'), $nm('Tania', 'f', 0.6, true), $ru('Татьяна', 'f'), $ru('Таня', 'f', 0.6, true)],
    ],
    [
        'code'  => 'sf_antonie', 'month' => 1, 'day' => 17, 'major' => false,
        'saint' => ['ro' => 'Sfântul Antonie cel Mare', 'ru' => 'Святой Антоний Великий', 'en' => 'Saint Anthony the Great'],
        'names' => [$nm('Antonie', 'm'), $nm('Anton', 'm'), $nm('Antonia', 'f'), $nm('Toni', null, 0.6, true), $ru('Антон', 'm'), $ru('Антонина', 'f', 0.8)],
    ],
    [
        'code'  => 'sf_atanasie_chiril', 'month' => 1, 'day' => 18, 'major' => false,
        'saint' => ['ro' => 'Sfinții Atanasie și Chiril', 'ru' => 'Святые Афанасий и Кирилл', 'en' => 'Saints Athanasius and Cyril'],
        'names' => [$nm('Atanasie', 'm'), $nm('Tănase', 'm', 0.8), $nm('Nasta', 'f', 0.4)],
    ],
    [
        'code'  => 'trei_ierarhi', 'month' => 1, 'day' => 30, 'major' => false,
        'saint' => ['ro' => 'Sfinții Trei Ierarhi', 'ru' => 'Три Святителя', 'en' => 'Three Holy Hierarchs'],
        'names' => [$nm('Grigore', 'm'), $nm('Grigorie', 'm'), $nm('Grig', 'm', 0.6, true), $ru('Григорий', 'm'), $ru('Гриша', 'm', 0.6, true)],
    ],
    [
        'code'  => 'sf_trifon', 'month' => 2, 'day' => 1, 'major' => false,
        'saint' => ['ro' => 'Sfântul Trifon', 'ru' => 'Святой Трифон', 'en' => 'Saint Tryphon'],
        'names' => [$nm('Trifan', 'm'), $nm('Trifon', 'm')],
    ],
    [
        'code'  => 'sf_haralambie', 'month' => 2, 'day' => 10, 'major' => false,
        'saint' => ['ro' => 'Sfântul Haralambie', 'ru' => 'Святой Харалампий', 'en' => 'Saint Charalampus'],
        'names' => [$nm('Haralambie', 'm'), $nm('Haralamb', 'm'), $nm('Lambe', 'm', 0.4, true)],
    ],
    [
        'code'  => 'sf_teodor_tiron', 'month' => 2, 'day' => 17, 'major' => false,
        'saint' => ['ro' => 'Sfântul Teodor Tiron', 'ru' => 'Святой Феодор Тирон', 'en' => 'Saint Theodore Tyron'],
        'names' => [
            $nm('Fedea', 'm', 0.6, true),
            $nm('Teodor', 'm'), $nm('Tudor', 'm', 0.8), $nm('Todor', 'm', 0.8), $nm('Teodora', 'f'),
            $nm('Doru', 'm', 0.6, true), $nm('Dorina', 'f', 0.6, true),
            $ru('Фёдор', 'm'), $ru('Федор', 'm'), $ru('Федя', 'm', 0.6, true),
        ],
    ],
    [
        'code'  => 'sf_alexie', 'month' => 3, 'day' => 17, 'major' => false,
        'saint' => ['ro' => 'Sfântul Alexie, omul lui Dumnezeu', 'ru' => 'Алексий, человек Божий', 'en' => 'Saint Alexius'],
        'names' => [
            $nm('Alioșa', 'm', 0.6, true), $nm('Liosa', 'm', 0.6, true), $nm('Alexie', 'm'), $nm('Alexei', 'm'), $ru('Алексей', 'm'), $ru('Алёша', 'm', 0.6, true), $ru('Лёша', 'm', 0.6, true)],
    ],
    [
        'code'  => 'sf_gheorghe', 'month' => 4, 'day' => 23, 'major' => true,
        'saint' => ['ro' => 'Sfântul Mare Mucenic Gheorghe', 'ru' => 'Святой Георгий Победоносец', 'en' => 'Saint George'],
        'names' => [
            $nm('Jora', 'm', 0.6, true), $nm('Iura', 'm', 0.6, true), $nm('Gosa', 'm', 0.4, true),
            $nm('Gheorghe', 'm'), $nm('George', 'm'), $nm('Georgian', 'm', 0.8), $nm('Georgiana', 'f'),
            $nm('Georgeta', 'f'), $nm('Gicu', 'm', 0.6, true), $nm('Gigi', 'm', 0.4, true),
            $nm('Georgel', 'm', 0.6, true), $nm('Gheorghiță', 'm', 0.6, true), $nm('Geta', 'f', 0.4, true),
            $nm('Iurie', 'm', 0.8), $nm('Egor', 'm', 0.8),
            $ru('Георгий', 'm'), $ru('Юрий', 'm', 0.8), $ru('Егор', 'm', 0.8),
            $ru('Жора', 'm', 0.6, true), $ru('Гоша', 'm', 0.6, true),
        ],
    ],
    [
        'code'  => 'sf_marcu', 'month' => 4, 'day' => 25, 'major' => false,
        'saint' => ['ro' => 'Sfântul Apostol și Evanghelist Marcu', 'ru' => 'Апостол и евангелист Марк', 'en' => 'Saint Mark the Evangelist'],
        'names' => [$nm('Marcu', 'm'), $nm('Marc', 'm'), $nm('Marius', 'm', 0.4), $ru('Марк', 'm')],
    ],
    [
        'code'  => 'sf_irina', 'month' => 5, 'day' => 5, 'major' => false,
        'saint' => ['ro' => 'Sfânta Mare Muceniță Irina', 'ru' => 'Святая великомученица Ирина', 'en' => 'Saint Irene'],
        'names' => [
            $nm('Irocica', 'f', 0.4, true), $nm('Irina', 'f'), $nm('Ira', 'f', 0.6, true), $ru('Ирина', 'f'), $ru('Ира', 'f', 0.6, true)],
    ],
    [
        'code'  => 'sf_chiril_metodiu', 'month' => 5, 'day' => 11, 'major' => false,
        'saint' => ['ro' => 'Sfinții Chiril și Metodiu', 'ru' => 'Святые Кирилл и Мефодий', 'en' => 'Saints Cyril and Methodius'],
        'names' => [$nm('Chiril', 'm'), $nm('Metodiu', 'm'), $ru('Кирилл', 'm')],
    ],
    [
        'code'  => 'sf_constantin_elena', 'month' => 5, 'day' => 21, 'major' => true,
        'saint' => ['ro' => 'Sfinții Împărați Constantin și Elena', 'ru' => 'Святые Константин и Елена', 'en' => 'Saints Constantine and Helena'],
        'names' => [
            $nm('Lena', 'f', 0.6, true), $nm('Aliona', 'f', 0.8), $nm('Kostea', 'm', 0.6, true),
            $nm('Constantin', 'm'), $nm('Costin', 'm', 0.8), $nm('Costel', 'm', 0.6, true),
            $nm('Costică', 'm', 0.6, true), $nm('Dinu', 'm', 0.4, true), $nm('Codrin', null, 0.4),
            $nm('Elena', 'f'), $nm('Ileana', 'f', 0.8), $nm('Lenuța', 'f', 0.6, true),
            $nm('Ela', 'f', 0.4, true), $nm('Nuți', 'f', 0.4, true), $nm('Alina', 'f', 0.4),
            $ru('Константин', 'm'), $ru('Костя', 'm', 0.6, true),
            $ru('Елена', 'f'), $ru('Лена', 'f', 0.6, true), $ru('Алёна', 'f', 0.8),
        ],
    ],
    [
        'code'  => 'sanzienele', 'month' => 6, 'day' => 24, 'major' => false,
        'saint' => ['ro' => 'Nașterea Sfântului Ioan Botezătorul (Sânzienele)', 'ru' => 'Рождество Иоанна Предтечи', 'en' => 'Nativity of Saint John the Baptist'],
        'names' => [$nm('Sânziana', 'f'), $nm('Ioan', 'm', 0.4)],
    ],
    [
        'code'  => 'sf_petru_pavel', 'month' => 6, 'day' => 29, 'major' => true,
        'saint' => ['ro' => 'Sfinții Apostoli Petru și Pavel', 'ru' => 'Святые апостолы Пётр и Павел', 'en' => 'Saints Peter and Paul'],
        'names' => [
            $nm('Petru', 'm'), $nm('Petre', 'm'), $nm('Petrică', 'm', 0.6, true), $nm('Petea', 'm', 0.6, true), $nm('Pașa', 'm', 0.6, true),
            $nm('Petronela', 'f', 0.8), $nm('Pavel', 'm'), $nm('Paul', 'm', 0.8), $nm('Paula', 'f', 0.8),
            $ru('Пётр', 'm'), $ru('Петр', 'm'), $ru('Петя', 'm', 0.6, true),
            $ru('Павел', 'm'), $ru('Паша', 'm', 0.6, true),
        ],
    ],
    [
        'code'  => 'sf_olga', 'month' => 7, 'day' => 11, 'major' => false,
        'saint' => ['ro' => 'Sfânta Olga', 'ru' => 'Святая равноапостольная княгиня Ольга', 'en' => 'Saint Olga'],
        'names' => [
            $nm('Olea', 'f', 0.6, true), $nm('Olga', 'f'), $ru('Ольга', 'f'), $ru('Оля', 'f', 0.6, true)],
    ],
    [
        'code'  => 'sf_vladimir', 'month' => 7, 'day' => 15, 'major' => false,
        'saint' => ['ro' => 'Sfântul Vladimir', 'ru' => 'Святой равноапостольный князь Владимир', 'en' => 'Saint Vladimir'],
        'names' => [
            $nm('Vova', 'm', 0.6, true), $nm('Volodea', 'm', 0.6, true), $nm('Vladimir', 'm'), $nm('Vlad', 'm', 0.8), $ru('Владимир', 'm'), $ru('Володя', 'm', 0.6, true), $ru('Вова', 'm', 0.6, true)],
    ],
    [
        'code'  => 'sf_ilie', 'month' => 7, 'day' => 20, 'major' => true,
        'saint' => ['ro' => 'Sfântul Prooroc Ilie Tesviteanul', 'ru' => 'Святой пророк Илия', 'en' => 'Holy Prophet Elijah'],
        'names' => [$nm('Ilie', 'm'), $nm('Ilinca', 'f'), $nm('Ilieș', 'm', 0.8), $nm('Iliana', 'f', 0.8), $ru('Илья', 'm'), $ru('Илия', 'm')],
    ],
    [
        'code'  => 'sf_maria_magdalena', 'month' => 7, 'day' => 22, 'major' => false,
        'saint' => ['ro' => 'Sfânta Maria Magdalena', 'ru' => 'Святая Мария Магдалина', 'en' => 'Saint Mary Magdalene'],
        'names' => [$nm('Magdalena', 'f'), $nm('Magda', 'f', 0.6, true), $ru('Магдалина', 'f')],
    ],
    [
        'code'  => 'sf_ana', 'month' => 7, 'day' => 25, 'major' => true,
        'saint' => ['ro' => 'Adormirea Sfintei Ana', 'ru' => 'Успение праведной Анны', 'en' => 'Dormition of Saint Anne'],
        'names' => [
            $nm('Ana', 'f'), $nm('Anca', 'f', 0.8), $nm('Anuța', 'f', 0.6, true), $nm('Anișoara', 'f', 0.6, true),
            $ru('Анна', 'f'), $ru('Аня', 'f', 0.6, true), $ru('Анюта', 'f', 0.6, true),
        ],
    ],
    [
        'code'  => 'sf_pantelimon', 'month' => 7, 'day' => 27, 'major' => false,
        'saint' => ['ro' => 'Sfântul Pantelimon', 'ru' => 'Святой Пантелеимон', 'en' => 'Saint Panteleimon'],
        'names' => [$nm('Pantelimon', 'm'), $nm('Panta', 'm', 0.4, true)],
    ],
    [
        'code'  => 'sf_maria_mare', 'month' => 8, 'day' => 15, 'major' => true,
        'saint' => ['ro' => 'Adormirea Maicii Domnului (Sfânta Maria Mare)', 'ru' => 'Успение Пресвятой Богородицы', 'en' => 'Dormition of the Theotokos'],
        'names' => [
            $nm('Mașa', 'f', 0.6, true), $nm('Masa', 'f', 0.4, true),
            $nm('Maria', 'f'), $nm('Marian', 'm'), $nm('Mariana', 'f'), $nm('Marioara', 'f', 0.8),
            $nm('Maricica', 'f', 0.6, true), $nm('Mărioara', 'f', 0.8), $nm('Mia', 'f', 0.4, true),
            $nm('Marinela', 'f', 0.6), $nm('Marin', 'm', 0.6),
            $ru('Мария', 'f'), $ru('Маша', 'f', 0.6, true), $ru('Марина', 'f', 0.8), $ru('Маруся', 'f', 0.6, true),
        ],
    ],
    [
        'code'  => 'sf_adrian_natalia', 'month' => 8, 'day' => 26, 'major' => true,
        'saint' => ['ro' => 'Sfinții Adrian și Natalia', 'ru' => 'Святые Адриан и Наталия', 'en' => 'Saints Adrian and Natalia'],
        'names' => [
            $nm('Natașa', 'f', 0.6, true), $nm('Natasa', 'f', 0.6, true),
            $nm('Adrian', 'm'), $nm('Adriana', 'f'), $nm('Adi', null, 0.4, true),
            $nm('Natalia', 'f'), $nm('Natalița', 'f', 0.6, true), $nm('Nataliya', 'f', 0.8),
            $ru('Адриан', 'm'), $ru('Наталья', 'f'), $ru('Наталия', 'f'), $ru('Наташа', 'f', 0.6, true),
        ],
    ],
    [
        'code'  => 'sf_alexandru', 'month' => 8, 'day' => 30, 'major' => true,
        'saint' => ['ro' => 'Sfântul Alexandru', 'ru' => 'Святой Александр', 'en' => 'Saint Alexander'],
        'names' => [
            $nm('Șura', null, 0.4, true), $nm('Sasha', null, 0.6, true),
            $nm('Alexandru', 'm'), $nm('Alexandra', 'f'), $nm('Alex', null, 0.8),
            $nm('Sandu', 'm', 0.6, true), $nm('Sanda', 'f', 0.6, true), $nm('Sașa', null, 0.6, true),
            $ru('Александр', 'm'), $ru('Александра', 'f'), $ru('Саша', null, 0.6, true), $ru('Шура', null, 0.4, true),
        ],
    ],
    [
        'code'  => 'sf_simeon', 'month' => 9, 'day' => 1, 'major' => false,
        'saint' => ['ro' => 'Sfântul Simeon Stâlpnicul', 'ru' => 'Святой Симеон Столпник', 'en' => 'Saint Simeon the Stylite'],
        'names' => [$nm('Simion', 'm'), $nm('Simona', 'f', 0.8), $nm('Sima', null, 0.4, true), $ru('Семён', 'm'), $ru('Семен', 'm')],
    ],
    [
        'code'  => 'sf_maria_mica', 'month' => 9, 'day' => 8, 'major' => false,
        'saint' => ['ro' => 'Nașterea Maicii Domnului (Sfânta Maria Mică)', 'ru' => 'Рождество Пресвятой Богородицы', 'en' => 'Nativity of the Theotokos'],
        'names' => [$nm('Maria', 'f', 0.6)],
    ],
    [
        'code'  => 'sf_sofia', 'month' => 9, 'day' => 17, 'major' => true,
        'saint' => ['ro' => 'Sfintele Sofia, Vera, Nadejda și Liubov', 'ru' => 'Святые София, Вера, Надежда и Любовь', 'en' => 'Saints Sophia, Faith, Hope and Love'],
        'names' => [
            $nm('Sonea', 'f', 0.6, true), $nm('Nadea', 'f', 0.6, true), $nm('Liudmila', 'f', 0.4),
            $nm('Sofia', 'f'), $nm('Sofica', 'f', 0.6, true), $nm('Vera', 'f'), $nm('Nadejda', 'f'),
            $nm('Nadia', 'f', 0.6, true), $nm('Liubov', 'f'), $nm('Liuba', 'f', 0.6, true),
            $ru('София', 'f'), $ru('Соня', 'f', 0.6, true), $ru('Вера', 'f'),
            $ru('Надежда', 'f'), $ru('Надя', 'f', 0.6, true), $ru('Любовь', 'f'), $ru('Люба', 'f', 0.6, true),
        ],
    ],
    [
        'code'  => 'sf_serghie', 'month' => 10, 'day' => 7, 'major' => false,
        'saint' => ['ro' => 'Sfinții Mucenici Serghie și Vah', 'ru' => 'Святые мученики Сергий и Вакх', 'en' => 'Saints Sergius and Bacchus'],
        'names' => [
            $nm('Serioja', 'm', 0.6, true), $nm('Serghei', 'm'), $nm('Sergiu', 'm'), $nm('Serghei', 'm'), $ru('Сергей', 'm'), $ru('Серёжа', 'm', 0.6, true)],
    ],
    [
        'code'  => 'cuvioasa_parascheva', 'month' => 10, 'day' => 14, 'major' => true,
        'saint' => ['ro' => 'Cuvioasa Parascheva', 'ru' => 'Преподобная Параскева', 'en' => 'Saint Parascheva'],
        'names' => [$nm('Parascheva', 'f'), $nm('Paraschiva', 'f'), $nm('Piti', 'f', 0.4, true), $ru('Параскева', 'f'), $ru('Прасковья', 'f', 0.8)],
    ],
    [
        'code'  => 'sf_dumitru', 'month' => 10, 'day' => 26, 'major' => true,
        'saint' => ['ro' => 'Sfântul Mare Mucenic Dimitrie', 'ru' => 'Святой великомученик Димитрий', 'en' => 'Saint Demetrius'],
        'names' => [
            $nm('Mitea', 'm', 0.6, true), $nm('Dmitri', 'm'),
            $nm('Dumitru', 'm'), $nm('Dumitra', 'f'), $nm('Dimitrie', 'm'), $nm('Mitică', 'm', 0.6, true),
            $nm('Mitruț', 'm', 0.6, true), $nm('Dima', 'm', 0.6, true), $nm('Demis', 'm', 0.4),
            $ru('Дмитрий', 'm'), $ru('Дима', 'm', 0.6, true), $ru('Митя', 'm', 0.6, true),
        ],
    ],
    [
        'code'  => 'sf_mihail_gavriil', 'month' => 11, 'day' => 8, 'major' => true,
        'saint' => ['ro' => 'Sfinții Arhangheli Mihail și Gavriil', 'ru' => 'Святые Архангелы Михаил и Гавриил', 'en' => 'Holy Archangels Michael and Gabriel'],
        'names' => [
            $nm('Mișa', 'm', 0.6, true), $nm('Misa', 'm', 0.6, true),
            $nm('Mihai', 'm'), $nm('Mihail', 'm'), $nm('Mihaela', 'f'), $nm('Mihaiță', 'm', 0.6, true),
            $nm('Misu', 'm', 0.4, true), $nm('Gabriel', 'm'), $nm('Gabriela', 'f'), $nm('Gavril', 'm'),
            $nm('Gavriil', 'm'), $nm('Gabi', null, 0.6, true),
            $ru('Михаил', 'm'), $ru('Миша', 'm', 0.6, true), $ru('Гавриил', 'm'),
        ],
    ],
    [
        'code'  => 'sf_mina', 'month' => 11, 'day' => 11, 'major' => false,
        'saint' => ['ro' => 'Sfântul Mare Mucenic Mina', 'ru' => 'Святой великомученик Мина', 'en' => 'Saint Menas'],
        'names' => [$nm('Mina', null)],
    ],
    [
        'code'  => 'sf_filip', 'month' => 11, 'day' => 14, 'major' => false,
        'saint' => ['ro' => 'Sfântul Apostol Filip', 'ru' => 'Святой апостол Филипп', 'en' => 'Saint Philip'],
        'names' => [$nm('Filip', 'm'), $ru('Филипп', 'm')],
    ],
    [
        'code'  => 'sf_matei', 'month' => 11, 'day' => 16, 'major' => false,
        'saint' => ['ro' => 'Sfântul Apostol și Evanghelist Matei', 'ru' => 'Святой апостол и евангелист Матфей', 'en' => 'Saint Matthew'],
        'names' => [$nm('Matei', 'm'), $ru('Матвей', 'm')],
    ],
    [
        'code'  => 'sf_ecaterina', 'month' => 11, 'day' => 25, 'major' => true,
        'saint' => ['ro' => 'Sfânta Mare Muceniță Ecaterina', 'ru' => 'Святая великомученица Екатерина', 'en' => 'Saint Catherine'],
        'names' => [
            $nm('Katea', 'f', 0.6, true), $nm('Katia', 'f', 0.6, true),
            $nm('Ecaterina', 'f'), $nm('Catrina', 'f', 0.8), $nm('Cătălina', 'f', 0.6),
            $nm('Cati', 'f', 0.4, true), $ru('Екатерина', 'f'), $ru('Катя', 'f', 0.6, true),
        ],
    ],
    [
        'code'  => 'sf_andrei', 'month' => 11, 'day' => 30, 'major' => true,
        'saint' => ['ro' => 'Sfântul Apostol Andrei', 'ru' => 'Святой апостол Андрей Первозванный', 'en' => 'Saint Andrew'],
        'names' => [
            $nm('Andrei', 'm'), $nm('Andreea', 'f'), $nm('Andriușa', 'm', 0.6, true), $nm('Andra', 'f', 0.6, true),
            $nm('Andrian', 'm', 0.4), $ru('Андрей', 'm'), $ru('Андрюша', 'm', 0.6, true),
        ],
    ],
    [
        'code'  => 'sf_varvara', 'month' => 12, 'day' => 4, 'major' => false,
        'saint' => ['ro' => 'Sfânta Mare Muceniță Varvara', 'ru' => 'Святая великомученица Варвара', 'en' => 'Saint Barbara'],
        'names' => [$nm('Varvara', 'f'), $nm('Barbara', 'f', 0.8), $ru('Варвара', 'f'), $ru('Варя', 'f', 0.6, true)],
    ],
    [
        'code'  => 'sf_sava', 'month' => 12, 'day' => 5, 'major' => false,
        'saint' => ['ro' => 'Sfântul Cuvios Sava cel Sfințit', 'ru' => 'Преподобный Савва Освященный', 'en' => 'Saint Sabbas'],
        'names' => [$nm('Sava', 'm')],
    ],
    [
        'code'  => 'sf_nicolae', 'month' => 12, 'day' => 6, 'major' => true,
        'saint' => ['ro' => 'Sfântul Ierarh Nicolae', 'ru' => 'Святитель Николай Чудотворец', 'en' => 'Saint Nicholas'],
        'names' => [
            $nm('Colea', 'm', 0.6, true), $nm('Kolea', 'm', 0.6, true),
            $nm('Nicolae', 'm'), $nm('Nicoleta', 'f'), $nm('Nicu', 'm', 0.6, true), $nm('Nicușor', 'm', 0.6, true),
            $nm('Niculina', 'f', 0.8), $nm('Culiță', 'm', 0.4, true), $nm('Nicolai', 'm'),
            $nm('Nico', null, 0.4, true),
            $ru('Николай', 'm'), $ru('Коля', 'm', 0.6, true), $ru('Николь', 'f', 0.6),
        ],
    ],
    [
        'code'  => 'sf_spiridon', 'month' => 12, 'day' => 12, 'major' => false,
        'saint' => ['ro' => 'Sfântul Ierarh Spiridon', 'ru' => 'Святитель Спиридон Тримифунтский', 'en' => 'Saint Spyridon'],
        'names' => [$nm('Spiridon', 'm'), $nm('Spiru', 'm', 0.6, true)],
    ],
    [
        'code'  => 'sf_daniel', 'month' => 12, 'day' => 17, 'major' => true,
        'saint' => ['ro' => 'Sfântul Prooroc Daniel', 'ru' => 'Святой пророк Даниил', 'en' => 'Holy Prophet Daniel'],
        'names' => [
            $nm('Daniel', 'm'), $nm('Daniela', 'f'), $nm('Dan', 'm', 0.8), $nm('Dana', 'f', 0.8),
            $nm('Dănuț', 'm', 0.6, true), $ru('Даниил', 'm'), $ru('Даниела', 'f', 0.8),
        ],
    ],
    [
        'code'  => 'sf_ignat', 'month' => 12, 'day' => 20, 'major' => false,
        'saint' => ['ro' => 'Sfântul Ignatie Teoforul', 'ru' => 'Священномученик Игнатий Богоносец', 'en' => 'Saint Ignatius'],
        'names' => [$nm('Ignat', 'm'), $nm('Ignatie', 'm')],
    ],
    [
        'code'  => 'sf_anastasia', 'month' => 12, 'day' => 22, 'major' => false,
        'saint' => ['ro' => 'Sfânta Mare Muceniță Anastasia', 'ru' => 'Святая великомученица Анастасия', 'en' => 'Saint Anastasia'],
        'names' => [
            $nm('Nastia', 'f', 0.6, true), $nm('Anastasia', 'f'), $nm('Anastasie', 'm', 0.8), $nm('Nastea', 'f', 0.6, true), $ru('Анастасия', 'f'), $ru('Настя', 'f', 0.6, true)],
    ],
    [
        'code'  => 'craciun', 'month' => 12, 'day' => 25, 'major' => true,
        'saint' => ['ro' => 'Nașterea Domnului (Crăciunul)', 'ru' => 'Рождество Христово', 'en' => 'Nativity of Christ'],
        'names' => [
            $nm('Cristian', 'm'), $nm('Cristina', 'f'), $nm('Cristi', null, 0.6, true),
            $nm('Cristiana', 'f', 0.8), $nm('Crăciun', 'm', 0.8), $ru('Кристина', 'f'), $ru('Кристиан', 'm'),
        ],
    ],
    [
        'code'  => 'sf_stefan', 'month' => 12, 'day' => 27, 'major' => true,
        'saint' => ['ro' => 'Sfântul Arhidiacon Ștefan', 'ru' => 'Святой первомученик Стефан', 'en' => 'Saint Stephen'],
        'names' => [
            $nm('Ștefan', 'm'), $nm('Ștefania', 'f'), $nm('Fane', 'm', 0.6, true), $nm('Ștefăniță', 'm', 0.6, true),
            $ru('Степан', 'm'), $ru('Стефан', 'm'), $ru('Стефания', 'f'),
        ],
    ],
    [
        'code'  => 'sf_melania', 'month' => 12, 'day' => 31, 'major' => false,
        'saint' => ['ro' => 'Sfânta Cuvioasă Melania', 'ru' => 'Преподобная Мелания Римляныня', 'en' => 'Saint Melania'],
        'names' => [$nm('Melania', 'f'), $nm('Mela', 'f', 0.4, true)],
    ],
];
