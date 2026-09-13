# 09 — Ecrane, fluxuri și copy

> Fără inventarul de ecrane, estimările din `PLAN.md` sunt ghicite.
> Și într-o aplicație de remindere, **copy-ul este produsul** — notificarea e singurul lucru pe care îl vede utilizatorul în 90% din interacțiuni.

---

## 1. Inventarul de ecrane — MVP

**32 de ecrane.** Numărul contează: la ~0,5–1,5 zile pe ecran (design + implementare + 3 limbi + stări), asta e aritmetica reală din spatele celor 12 săptămâni.

### Onboarding (9)

| # | Ecran | Rol | Stări speciale |
|---|---|---|---|
| O1 | Alegere limbă | RO/RU/EN, prima decizie | auto-detectat, cu confirmare |
| O2 | Valoare | 3 carduri: nu uiți · știi ce iei · de unde cumperi | — |
| O3 | Autentificare | Apple, Google, email OTP | eroare rețea, cod greșit |
| O4 | Verificare cod OTP | doar pe ruta email | cod expirat, retrimitere |
| O5 | **Explicație Contacts** | obligatoriu înainte de promptul nativ (App Store 5.1.2) | — |
| O6 | Selecție persoane | listă cu bifare, căutare, „selectează toate" | 0 contacte, permisiune refuzată |
| O7 | **Confirmare onomastici** | „Gheorghe → 23 aprilie. Corect?" | niciuna detectată |
| O8 | **Ocazii găsite (aha moment)** | „Am găsit 4 zile de naștere și 112 onomastici" | 0 ocazii → rută spre O9b |
| O9 | Cerere push | *după* aha moment, niciodată înainte | refuzat → explicăm digest-ul |
| O9b | Adăugare manuală rapidă | fallback dacă nu sunt contacte | — |

> O5 → O8 este secvența cu cel mai mare risc din produs. Dacă pică aici, restul nu contează. Vezi `docs/02 § R1`.

### Acasă (3)

| # | Ecran | Rol |
|---|---|---|
| H1 | Acasă | „La cine trebuie să te gândești azi?" + următoarele ocazii |
| H2 | Detaliu ocazie | ce e, când, ce poți face: cadou / experiență / mesaj |
| H3 | Calendar ocazii | vizualizare lunară, toate tipurile |

### Persoane (7)

| # | Ecran | Rol |
|---|---|---|
| P1 | Listă persoane | grupate: azi / săptămâna asta / luna asta / mai târziu; căutare |
| P2 | Detaliu persoană | ocazii, interese, buget, note, istoric cadouri, idei salvate |
| P3 | Adăugare/editare persoană | nume, dată, relație, gen, buget |
| P4 | Selector interese | din taxonomie, în limba userului, cu căutare |
| P5 | **Onboarding AI persoană** | o frază liberă → interese extrase, confirmate de user |
| P6 | Note și „de evitat" | privat, criptat; semnale negative |
| P7 | **Cine este?** | o completare din link al cărei nume seamănă cu contacte existente → proprietarul alege contactul sau „altcineva” (S9.8) |

### Recomandări (5)

| # | Ecran | Rol | Stări speciale |
|---|---|---|---|
| R1 | Alegere buget + tip | cadou sau experiență | — |
| R2 | **Consimțământ AI** | o singură dată, explicit (Apple, nov. 2025) | refuz → rută doar-filtre |
| R3 | Se caută idei | job asincron, nu blocăm request-ul | timeout, eroare |
| R4 | Rezultate | 5–8 carduri, motiv scurt, preț, magazin | **0 rezultate** — cel mai important gol |
| R5 | Detaliu produs | poză, preț, magazin, buton spre magazin, salvează idee | produs indisponibil |

### Cadouri (3)

| # | Ecran | Rol |
|---|---|---|
| G1 | Idei salvate | per persoană și global |
| G2 | Marchează „am oferit" | intră în istoric, alimentează anti-repetarea |
| G3 | Istoric cadouri | timeline per persoană, pe ani |

### Profilul meu (7)

| # | Ecran | Rol |
|---|---|---|
| M1 | Profilul meu | ziua mea, interese, oraș |
| M2 | Ce îmi doresc | produse |
| M3 | Unde aș vrea să merg | locuri și experiențe |
| M4 | **Vizibilitate per câmp** | private / signal_only / contacts / public |
| M5 | **Linkul meu public** | `@slug`, previzualizare, partajare, statistici |
| M6 | Setări notificări | zile înainte, oră, quiet hours, digest |
| M7 | Cont | limbă, **export date**, **ștergere cont**, legal |

### Web public (3) — Inertia + Vue, RO/RU/EN

| # | Pagină | Rol |
|---|---|---|
| W1 | `@slug` | profil public, în limba vizitatorului |
| W2 | Formular completare | ziua + interese + **consimțământ versionat** |
| W3 | Mulțumim + instalează | închide bucla virală |

### Transversale (nu sunt ecrane, sunt obligații)

Fiecare listă are **stare goală**, **stare de eroare**, **stare offline** și **skeleton de încărcare**. Fiecare, în trei limbi. Asta e ~25% din efortul de UI și e motivul pentru care S11 există.

---

## 2. Fluxurile care contează

### F1 — Onboarding (de la instalare la aha)
```
O1 limbă → O2 valoare → O3/O4 cont → O5 explicație → [prompt nativ Contacts]
   ├─ permis  → O6 selecție → O7 onomastici → O8 AHA → O9 push → H1
   └─ refuzat → O9b adăugare manuală → O8 AHA → O9 push → H1
```
**Regula:** ambele ramuri ajung la aha. Aplicația funcționează integral fără Contacts.

### F2 — De la reminder la magazin *(bucla care justifică produsul)*
```
push → H2 detaliu ocazie → „Găsește un cadou" → R1 buget
     → R2 consimțământ (o dată) → R3 se caută → R4 rezultate
     → R5 produs → [outbound_click] → magazin
     → înapoi în app: „Ai cumpărat?" → G2 → istoric
```
Fiecare săgeată e un eveniment în `docs/07`. Dacă rata de trecere pe vreuna e sub prag, acolo e problema.

### F3 — Bucla virală
```
M5 linkul meu → partajat manual în WhatsApp/Telegram (NICIODATĂ de aplicație)
   → W1 prietenul deschide → W2 completează + consimțământ
   → datele ajung la mine cu source=subject_provided (trust nivel 2)
   → W3 „și tu?" → instalare → F1
```

### F4 — Persoană nouă
```
P3 adaugă → P5 „Spune-mi despre Alex" (frază liberă)
   → AI extrage interese → user confirmă/corectează → P2
```

---

## 3. Copy — principii

Într-o aplicație de remindere, textul notificării *este* interfața. Reguli:

1. **Numele persoanei în primele 3 cuvinte.** „Alex are ziua peste 5 zile", nu „Ai o ocazie apropiată".
2. **Un singur verb de acțiune.** Nu oferi 4 opțiuni în push.
3. **Fără vină.** Niciodată „Ai uitat...". Reminderul e ajutor, nu reproș.
4. **Fără urgență falsă.** Fără „ULTIMA ȘANSĂ". Distruge încrederea pe termen lung.
5. **Escaladare, nu repetare.** Fiecare notificare din serie spune altceva.
6. **Textele se scriu direct în RO și RU**, nu se traduc din engleză. Traducerea se simte.
7. **Verifică lungimea în RU** — cu 10–15% mai lung; notificările iOS se taie la ~40 de caractere în titlu.

### Scara de notificări — ce spune fiecare

| Când | Intenție | RO | RU |
|---|---|---|---|
| 14 zile | doar semnalizează | „Alex are ziua pe 15 septembrie" | «У Alex день рождения 15 сентября» |
| 7 zile | invită la acțiune | „Alex are ziua peste 7 zile. Găsim un cadou?" | «День рождения Alex через 7 дней. Подберём подарок?» |
| 3 zile | urgență blândă | „Peste 3 zile e ziua lui Alex" | «Через 3 дня день рождения Alex» |
| 1 zi | ultimul moment util | „Mâine e ziua lui Alex" | «Завтра день рождения Alex» |
| în zi | doar uman | „Astăzi e ziua lui Alex 🎂" | «Сегодня день рождения Alex 🎂» |

**Onomastici:** aceeași scară, dar doar **7 / 1 / în zi** — sunt mai multe și mai puțin importante. Vezi `config/wishio.php`.

**Titlu și corp.** Titlul ține numele și momentul și încape în ~40 de caractere, cât arată iOS: „Ana are ziua peste 7 zile”. Invitația („Găsim un cadou?”) stă în corpul notificării, de la 5 zile în sus. Textele din tabel descriu intenția fiecărei trepte; formularea exactă e în `lang/*/wishio.php`.

### Alte notificări

| Când | RO | RU |
|---|---|---|
| o completare din link așteaptă „cine este?" | „Ana Popescu ți-a completat linkul" | «Ana Popescu: новая анкета» |
| mai multe completări așteaptă | „3 completări așteaptă răspunsul tău" | «3 анкеты ждут ответа» |

O singură notificare pentru completările venite în rafală, niciodată în orele de liniște, cel mult una pe zi. Limita zilnică de push e comună cu reminderele.

### Copy care trebuie scris (nu e opțional)

| Suprafață | Volum | Când |
|---|---|---|
| Onboarding (O1–O9) | ~40 de texte × 3 limbi | S1–S4 |
| Notificări (5 tipuri × 5 momente) | ~25 × 3 | S5 |
| Stări goale și erori | ~30 × 3 | continuu |
| Explicații de permisiuni și consimțământ | ~10 × 3 | S4, S7 |
| Emailuri (OTP, digest, bun venit) | ~8 × 3 | S5 |
| Pagini publice + store listing | ~30 × 3 | S9, S12 |

**~150 de texte × 3 limbi.** Asta nu se face „la final". E în Definition of Done al fiecărui ecran.
