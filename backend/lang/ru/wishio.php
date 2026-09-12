<?php

return [
    'occasions' => [
        'birthday'    => 'День рождения',
        'name_day'    => 'Именины',
        'anniversary' => 'Годовщина',
        'holiday'     => 'Праздник',
        'custom'      => 'Особый повод',
    ],
    'reminder' => [
        // Rusa are 3 forme de plural: 1 / 2-4 / 5+
        'days_before' => '{1} У :name :occasion завтра|[2,4] У :name :occasion через :count дня|[5,*] У :name :occasion через :count дней',
        'today'       => 'Сегодня у :name :occasion',
        'cta_gift'    => 'Подобрать подарок',
    ],
];
