<?php

return [
    'occasions' => [
        'birthday'    => 'Zi de naștere',
        'name_day'    => 'Onomastică',
        'anniversary' => 'Aniversare',
        'holiday'     => 'Sărbătoare',
        'custom'      => 'Ocazie personalizată',
    ],
    'push' => [
        // Construcții neutre la gen: „își serbează” merge și pentru Alex, și
        // pentru Ana. Genitivul românesc („a lui / a Anei”) ar fi cerut să
        // ghicim genul, iar deseori nu-l știm.
        'today'    => ':name își serbează :occasion astăzi 🎂',
        'tomorrow' => ':name își serbează :occasion mâine',
        'soon'     => '{1} :name își serbează :occasion peste o zi|[2,19] :name își serbează :occasion peste :count zile|[20,*] :name își serbează :occasion peste :count de zile',
        'plan'     => '{1} :name are :occasion peste o zi. Găsim un cadou?|[2,19] :name are :occasion peste :count zile. Găsim un cadou?|[20,*] :name are :occasion peste :count de zile. Găsim un cadou?',
        'cta'      => 'Găsește un cadou',
    ],

    'reminder' => [
        'days_before' => '{1} :name are :occasion mâine|[2,4] :name are :occasion peste :count zile|[5,*] :name are :occasion peste :count de zile',
        'today'       => 'Astăzi este :occasion pentru :name',
        'cta_gift'    => 'Găsește un cadou',
    ],
];
