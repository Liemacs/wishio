<?php

return [
    'occasions' => [
        'birthday'    => 'День рождения',
        'name_day'    => 'Именины',
        'anniversary' => 'Годовщина',
        'holiday'     => 'Праздник',
        'custom'      => 'Особый повод',
    ],
    'holiday' => [
        'today'    => 'Сегодня :holiday 🎉',
        'tomorrow' => 'Завтра :holiday',
        'soon'     => '{1} :holiday через день|[2,4] :holiday через :count дня|[5,*] :holiday через :count дней',
        'people'   => '{0} |{1} Один человек в вашем списке|[2,4] :count человека в вашем списке|[5,*] :count человек в вашем списке',
    ],

    'push' => [
        'today'    => 'У :name сегодня :occasion 🎂',
        'tomorrow' => 'У :name завтра :occasion',
        'soon'     => '{1} У :name :occasion через день|[2,4] У :name :occasion через :count дня|[5,*] У :name :occasion через :count дней',
        'plan'     => '{1} У :name :occasion через день. Подберём подарок?|[2,4] У :name :occasion через :count дня. Подберём подарок?|[5,*] У :name :occasion через :count дней. Подберём подарок?',
        'cta'      => 'Подобрать подарок',
    ],

    'reminder' => [
        // Rusa are 3 forme de plural: 1 / 2-4 / 5+
        'days_before' => '{1} У :name :occasion завтра|[2,4] У :name :occasion через :count дня|[5,*] У :name :occasion через :count дней',
        'today'       => 'Сегодня у :name :occasion',
        'cta_gift'    => 'Подобрать подарок',
    ],
];
