<?php

return [
    'occasions' => [
        'birthday'    => 'Zi de naștere',
        'name_day'    => 'Onomastică',
        'anniversary' => 'Aniversare',
        'holiday'     => 'Sărbătoare',
        'custom'      => 'Ocazie personalizată',
    ],
    'digest' => [
        'subject'            => '{1} O ocazie în perioada următoare|[2,19] :count ocazii în perioada următoare|[20,*] :count de ocazii în perioada următoare',
        'greeting'           => 'Bună, :name',
        'intro'              => 'Iată ce urmează în următoarele 30 de zile.',
        'today'              => 'astăzi',
        'tomorrow'           => 'mâine',
        'in_days'            => '{1} peste o zi|[2,19] peste :count zile|[20,*] peste :count de zile',
        'cta'                => 'Deschide Wishio',
        'unsubscribe'        => 'Nu mai vreau acest email',
        'unsubscribed_title' => 'Nu mai primești rezumatul săptămânal',
        'unsubscribed_body'  => 'Gata, te-am scos de pe listă. Îl poți reporni oricând din Notificări, în aplicație.',
        'footer'             => 'Primești acest email pentru că ai activat rezumatul săptămânal în Wishio.',
    ],

    'holiday' => [
        'today'    => 'Astăzi e :holiday 🎉',
        'tomorrow' => 'Mâine e :holiday',
        'soon'     => '{1} :holiday e peste o zi|[2,19] :holiday e peste :count zile|[20,*] :holiday e peste :count de zile',
        'people'   => '{0} |{1} O persoană pe lista ta|[2,19] :count persoane pe lista ta|[20,*] :count de persoane pe lista ta',
    ],

    'push' => [
        'today'    => ':name are :occasion astăzi 🎂',
        'tomorrow' => ':name are :occasion mâine',
        'soon'     => '{1} :name are :occasion peste o zi|[2,19] :name are :occasion peste :count zile|[20,*] :name are :occasion peste :count de zile',
        // Invitația la cadou stă în corpul notificării: titlul trebuie să încapă în ~40 de caractere.
        'ask' => 'Găsim un cadou?',
        'cta' => 'Găsește un cadou',
        // Forma din propoziție: „Ana are ziua”, nu „Ana are zi de naștere”.
        'occasion' => [
            'birthday'    => 'ziua',
            'name_day'    => 'onomastica',
            'anniversary' => 'aniversarea',
            'custom'      => 'o ocazie',
        ],
    ],

    'reminder' => [
        'days_before' => '{1} :name are :occasion mâine|[2,4] :name are :occasion peste :count zile|[5,*] :name are :occasion peste :count de zile',
        'today'       => 'Astăzi este :occasion pentru :name',
        'cta_gift'    => 'Găsește un cadou',
    ],

    // „Cine este?” — completări din link care așteaptă alegerea proprietarului (S9.8)
    'submissions' => [
        'one_title'  => ':name ți-a completat linkul',
        'one_body'   => 'Spune-ne cine este, ca ziua să ajungă la persoana potrivită.',
        'many_title' => '{1} O completare așteaptă răspunsul tău|[2,19] :count completări așteaptă răspunsul tău|[20,*] :count de completări așteaptă răspunsul tău',
        'many_body'  => 'Spune-ne cine sunt, ca zilele să ajungă la persoanele potrivite.',
    ],
];
