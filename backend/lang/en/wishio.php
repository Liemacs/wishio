<?php

return [
    'occasions' => [
        'birthday'    => 'Birthday',
        'name_day'    => 'Name day',
        'anniversary' => 'Anniversary',
        'holiday'     => 'Holiday',
        'custom'      => 'Custom occasion',
    ],
    'push' => [
        'today'    => ":name has a :occasion today 🎂",
        'tomorrow' => ':name has a :occasion tomorrow',
        'soon'     => '{1} :name has a :occasion in one day|[2,*] :name has a :occasion in :count days',
        'plan'     => '{1} :name has a :occasion in one day. Shall we find a gift?|[2,*] :name has a :occasion in :count days. Shall we find a gift?',
        'cta'      => 'Find a gift',
    ],

    'reminder' => [
        'days_before' => '{1} :name has a :occasion tomorrow|[2,*] :name has a :occasion in :count days',
        'today'       => "Today is :name's :occasion",
        'cta_gift'    => 'Find a gift',
    ],
];
