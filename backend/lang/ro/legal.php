<?php

/**
 * Politica de confidențialitate și termenii.
 *
 * Conținutul descrie exact ce face aplicația — orice modificare de
 * funcționalitate care atinge date personale cere și o modificare aici.
 * Vezi docs/06-privacy-legal.md § 3 pentru harta datelor.
 *
 * ⚠️ De revizuit de un jurist înainte de lansare (docs/08 § C11).
 */

return [
    'updated'  => 'Ultima actualizare: 12 septembrie 2026',
    'operator' => 'Operatorul datelor',

    'privacy' => [
        'title' => 'Politica de confidențialitate',
        'intro' => 'Wishio te ajută să nu uiți ocaziile importante ale oamenilor apropiați. Ca să facă asta, prelucrează unele date personale — ale tale și ale persoanelor pe care le adaugi. Mai jos scrie exact care, de ce și cât timp.',

        'sections' => [
            [
                'title' => 'Ce colectăm despre tine',
                'items' => [
                    'Numele și adresa de email, ca să ai un cont.',
                    'Limba, fusul orar și țara, ca aplicația să funcționeze corect.',
                    'Ziua ta de naștere și interesele tale, dacă le completezi.',
                    'Preferințele de notificare și identificatorul dispozitivului, ca să-ți putem trimite remindere.',
                ],
            ],
            [
                'title' => 'Ce colectăm despre persoanele pe care le adaugi',
                'items' => [
                    'Numele și, dacă există, ziua de naștere — din agenda telefonului, doar pentru contactele pe care le selectezi tu, sau introduse manual de tine.',
                    'Relația, interesele, bugetul și notele tale despre ele. Notele sunt criptate și nu părăsesc serverul.',
                    'Istoricul cadourilor pe care le-ai oferit, dacă îl completezi.',
                ],
            ],
            [
                'title' => 'Ce NU colectăm',
                'items' => [
                    'Nu citim și nu stocăm numere de telefon din agenda ta.',
                    'Nu citim fotografii, emailuri sau adrese din agendă.',
                    'Nu urcăm agenda ta pe server — doar contactele pe care le alegi explicit.',
                    'Nu arătăm datele tale altor utilizatori și nu construim profiluri partajate.',
                ],
            ],
            [
                'title' => 'Inteligența artificială',
                'items' => [
                    'Dacă accepți, trimitem unui furnizor extern de inteligență artificială: vârsta aproximativă, relația, interesele și bugetul.',
                    'NU trimitem niciodată numele, adresa de email, numărul de telefon sau notele tale.',
                    'Poți refuza. Aplicația funcționează integral și fără această opțiune — primești aceleași sugestii, fără explicații.',
                    'Poți schimba alegerea oricând, din setări.',
                ],
            ],
            [
                'title' => 'Temeiul legal',
                'items' => [
                    'Executarea contractului, pentru datele contului și funcționarea serviciului.',
                    'Interesul legitim, pentru datele persoanelor din agenda ta, folosite exclusiv în beneficiul tău personal.',
                    'Consimțământul, pentru trimiterea datelor către furnizorul de inteligență artificială, pentru notificări și pentru datele completate de alte persoane prin linkul tău public.',
                ],
            ],
            [
                'title' => 'Cui transmitem date',
                'items' => [
                    'Furnizorului de găzduire, pentru a rula serviciul.',
                    'Furnizorului de notificări, pentru a le livra pe telefonul tău.',
                    'Furnizorului de inteligență artificială, doar dacă ai acceptat și doar datele enumerate mai sus.',
                    'Nu vindem date. Nu le dăm nimănui în scop de publicitate.',
                ],
            ],
            [
                'title' => 'Cât timp le păstrăm',
                'items' => [
                    'Datele contului, cât timp contul există.',
                    'Clickurile spre magazine, 24 de luni, apoi doar agregat.',
                    'La ștergerea contului, tot ce ține de el se șterge definitiv, nu se dezactivează.',
                ],
            ],
            [
                'title' => 'Drepturile tale',
                'items' => [
                    'Poți descărca toate datele tale, din aplicație.',
                    'Poți șterge contul și toate datele, din aplicație, fără să ne scrii.',
                    'Poți cere rectificarea, restricționarea sau opoziția la prelucrare.',
                    'Dacă ai completat formularul cuiva prin linkul lui public, poți șterge ce ai trimis din linkul primit la final, fără cont.',
                    'Te poți adresa Centrului Național pentru Protecția Datelor cu Caracter Personal.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Termeni și condiții',
        'intro' => 'Folosind Wishio, ești de acord cu cele de mai jos.',

        'sections' => [
            [
                'title' => 'Serviciul',
                'items' => [
                    'Wishio îți amintește de ocazii și îți sugerează cadouri din magazine terțe.',
                    'Nu vindem produse. Cumpărarea se face la magazin, pe răspunderea și în condițiile lui.',
                    'Prețurile și disponibilitatea vin de la magazine și se pot schimba fără preaviz.',
                ],
            ],
            [
                'title' => 'Contul tău',
                'items' => [
                    'Ești responsabil de datele de acces.',
                    'Adaugi persoane doar în scop personal. Nu folosi Wishio ca să colectezi date despre oameni în alt scop.',
                    'Nu încărca date sensibile despre alte persoane: sănătate, convingeri, orientare, apartenență politică.',
                ],
            ],
            [
                'title' => 'Sugestiile',
                'items' => [
                    'Sugestiile sunt orientative. Alegerea finală și potrivirea cadoului îți aparțin.',
                    'Produsele sponsorizate sunt marcate ca atare.',
                ],
            ],
            [
                'title' => 'Încetarea',
                'items' => [
                    'Poți șterge contul oricând, din aplicație.',
                    'Putem suspenda un cont folosit abuziv sau ilegal.',
                ],
            ],
        ],
    ],
];
