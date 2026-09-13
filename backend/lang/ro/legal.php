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
    'updated'  => 'Ultima actualizare: 13 septembrie 2026',
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
                    'Ce oferte deschizi din aplicație spre magazine și când, ca să știm dacă sugestiile sunt utile.',
                ],
            ],
            [
                'title' => 'Ce colectăm despre persoanele pe care le adaugi',
                'items' => [
                    'Numele și, dacă există, ziua de naștere — din agenda telefonului, doar pentru contactele pe care le selectezi tu, sau introduse manual de tine.',
                    'Relația, genul, interesele, bugetul și notele tale despre ele. Notele sunt criptate și nu părăsesc serverul.',
                    'Ideile de cadou și istoricul cadourilor oferite, dacă le completezi.',
                    'Ce îți trimit alți oameni prin linkul tău public: numele, ziua de naștere, interesele și un mesaj scurt.',
                ],
            ],
            [
                'title' => 'Ce NU colectăm',
                'items' => [
                    'Nu citim și nu stocăm numere de telefon din agenda ta.',
                    'Nu citim emailuri sau adrese din agendă.',
                    'Pozele contactelor se afișează doar în aplicație, pe telefonul tău, și nu ajung pe serverele noastre.',
                    'Nu urcăm agenda ta pe server — doar contactele pe care le alegi explicit.',
                    'Nu arătăm datele tale altor utilizatori și nu construim profiluri partajate.',
                ],
            ],
            [
                'title' => 'Inteligența artificială',
                'items' => [
                    'Dacă accepți, trimitem unui furnizor extern de inteligență artificială: vârsta aproximativă, genul, relația, ocazia, bugetul, interesele și ce să evităm (alese din lista aplicației) și cadourile din catalogul nostru pe care i le-ai oferit deja.',
                    'NU trimitem niciodată numele, adresa de email, numărul de telefon, notele tale sau alt text scris de tine.',
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
                    'Furnizorului de notificări, prin Apple sau Google, pentru a le livra pe telefonul tău. O notificare conține numele persoanei și ocazia.',
                    'Furnizorului de email, pentru rezumatul săptămânal, cât timp nu te dezabonezi.',
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
                    'Copiile de siguranță mai păstrează datele cel mult 3 luni după ștergere, apoi sunt suprascrise.',
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

    'support' => [
        'title'    => 'Ajutor și contact',
        'intro'    => 'Ai o întrebare, ai dat de o greșeală sau vrei să ne spui ce lipsește? Scrie-ne la adresa de mai jos. Citim fiecare mesaj.',
        'sections' => [
            [
                'title' => 'Întrebări frecvente',
                'items' => [
                    'Nu primesc remindere. Verifică în setările telefonului că Wishio are voie să trimită notificări, apoi, în aplicație: Profilul meu → Notificări.',
                    'O onomastică e greșită. O poți respinge sau îi poți corecta data, iar Wishio păstrează alegerea ta.',
                    'Vreau să adaug pe cineva fără agendă. Din Persoane → Adaugă persoană. Aplicația funcționează complet și fără acces la contacte.',
                    'Cum îmi șterg contul? Din Profilul meu → Cont → Șterge contul. Pașii și ce se păstrează sunt pe pagina despre ștergerea contului.',
                ],
            ],
            [
                'title' => 'Datele tale',
                'items' => [
                    'Ce colectăm și de ce scrie în politica de confidențialitate.',
                    'Îți poți descărca oricând toate datele din Profilul meu → Cont.',
                ],
            ],
        ],
    ],

    'delete-account' => [
        'title'    => 'Ștergerea contului Wishio',
        'intro'    => 'Îți poți șterge contul oricând, cu tot ce ține de el. Ștergerea e definitivă: datele nu se mai pot recupera.',
        'sections' => [
            [
                'title' => 'Din aplicație',
                'items' => [
                    'Deschide Wishio și mergi la Profilul meu → Cont.',
                    'Apasă „Șterge contul”.',
                    'Confirmă scriind adresa de email a contului și parola.',
                ],
            ],
            [
                'title' => 'Fără aplicație',
                'items' => [
                    'Scrie-ne de pe adresa de email a contului, la adresa de mai jos, și cere ștergerea.',
                    'Ștergem contul în cel mult 30 de zile și îți confirmăm pe email.',
                ],
            ],
            [
                'title' => 'Ce se șterge',
                'items' => [
                    'Contul: numele, emailul, parola și setările.',
                    'Persoanele pe care le-ai adăugat, cu ocaziile, notele, interesele, ideile și istoricul cadourilor.',
                    'Linkul tău public, lista de dorințe și ce ți-au trimis alții prin link.',
                    'Dispozitivele conectate, reminderele, recomandările și clickurile spre magazine.',
                ],
            ],
            [
                'title' => 'Ce rămâne',
                'items' => [
                    'Copiile de siguranță mai conțin datele cel mult 3 luni, apoi sunt suprascrise.',
                    'Nimic altceva: datele nu se dezactivează, se șterg.',
                ],
            ],
        ],
    ],

];
