# 15 — Onomastici: cum funcționează și ce trebuie verificat

> Cel mai important activ de cold-start al produsului. Din 200 de contacte, poate 8 au ziua de naștere completată — dar ~150 au prenume.
> Vezi `docs/02 § R1` și `docs/04 § 4`.

---

## 1. Ce s-a construit

| Componentă | Unde |
|---|---|
| Tabele `name_days` + `name_day_aliases` | `backend/database/migrations/2026_09_12_1200*` |
| Date sursă — 49 de sărbători, 21 majore | `backend/database/seeders/data/name_days.php` |
| Seeder, cu generarea calendarului vechi | `backend/database/seeders/NameDaySeeder.php` |
| Normalizator de nume | `backend/app/Support/Names/NameNormalizer.php` |
| Resolver | `backend/app/Domain/Occasions/Actions/ResolveNameDay.php` |
| Teste | `backend/tests/Unit/NameNormalizerTest.php`, `tests/Feature/ResolveNameDayTest.php` |

**În bază:** 98 de intrări (49 stil nou + 49 stil vechi), **658 de aliasuri**.

---

## 2. Cele două calendare

În Moldova coexistă două calendare ortodoxe, iar produsul trebuie să le suporte pe amândouă:

| `calendar` | Cine | Exemplu |
|---|---|---|
| `orthodox_new` | Mitropolia Basarabiei (Patriarhia Română) | Sf. Gheorghe — 23 aprilie |
| `orthodox_old` | Mitropolia Moldovei (Patriarhia Moscovei) | Sf. Gheorghe — 6 mai |

**Varianta veche nu se scrie de mână.** Sărbătorile fixe pe stil vechi cad la data pe stil nou **+ 13 zile**, iar seeder-ul o generează. Regula se verifică pe cazuri cunoscute:

| Sărbătoare | Stil nou | Generat | Realitate |
|---|---|---|---|
| Sf. Nicolae | 6 dec | 19 dec | ✅ |
| Sf. Gheorghe | 23 apr | 6 mai | ✅ „Sf. Gheorghe pe stil vechi" |
| Sf. Tatiana | 12 ian | 25 ian | ✅ «Татьянин день» |
| Adormirea Maicii Domnului | 15 aug | 28 aug | ✅ «Успение 28 августа» |

**Regula de produs:** calendarul implicit se alege din limba utilizatorului — `ro` → stil nou, `ru` → stil vechi — și poate fi schimbat per persoană. Nu întrebăm pe nimeni la onboarding ce confesiune are.

---

## 3. Potrivirea numelor

### Trei categorii de aliasuri

| Tip | Exemplu | Încredere |
|---|---|---|
| Formă exactă | `Gheorghe`, `Николай` | 1.00 |
| Variantă | `George`, `Iurie`, `Egor` | 0.80 |
| Diminutiv | `Gicu`, `Coala`, `Mișa` | 0.60 |
| Ambiguu | `Gigi`, `Șura` | 0.40 |

### Forme rusești în alfabet latin — nu sunt opționale

În Moldova agendele conțin masiv `Tanea`, `Colea`, `Mișa`, `Natașa`, `Serioja`, `Jora`, `Mașa` — nume rusești transcrise, nu chirilice. Fără ele pierzi o mare parte din contactele vorbitorilor de rusă. Sunt în date și acoperite de test.

### Normalizarea trebuie să oglindească colația bazei

Aceasta e o capcană subtilă și costisitoare. Normalizatorul PHP și colația MySQL trebuie să producă **aceleași clase de echivalență**, altfel căutările diferă de constrângerile de unicitate. Verificat empiric pe MariaDB 10.4:

| Pereche | `utf8mb4_unicode_ci` | PHP |
|---|---|---|
| `ă` = `a`, `ș` = `s`, `ț` = `t` | egale | egale ✅ |
| `ё` = `е` | **egale** | egale ✅ *(descoperit prin eroare de unicitate la seed)* |
| `й` = `и` | diferite | diferite ✅ |
| `щ` = `ш` | diferite | diferite ✅ |
| `ь` ignorat | nu | nu ✅ |

Româna se scrie cu **două seturi de caractere** pentru `ș` și `ț` — cu virgulă (U+0219/U+021B, corect) și cu sedilă (U+015F/U+0163, moștenire Windows-1250). Agendele reale conțin ambele; normalizatorul le tratează pe amândouă.

### Ponderea pe poziția tokenului

Primul token dintr-un nume de contact este aproape întotdeauna prenumele, în ambele culturi. O potrivire pe un token ulterior e reală, dar mai puțin sigură:

```
"Ion Popescu"  →  ion 1.0 · popescu 0.8   →  Sf. Ioan, încredere 1.00
"Popescu Ion"  →  popescu 1.0 · ion 0.8   →  Sf. Ioan, încredere 0.80
```

Ponderea e pe token, **nu pe poziția în listă** — numele compus („maria elena") ocupă altfel prima poziție și ar penaliza greșit prenumele propriu-zis. Aceasta a fost prima versiune și a picat la teste.

### Ce respinge

Agendele sunt pline de intrări care nu sunt prenume: `Mama`, `Taxi`, `Sefu`, `Doctor`, `мама`, `работа`. Sunt filtrate explicit. Resolver-ul **nu inventează niciodată** o onomastică — testat.

---

## 4. ⚠️ Verificarea datelor — obligatorie înainte de lansare

**Toate cele 49 de sărbători au `is_verified = false`.** Datele au fost scrise ca punct de plecare, nu confruntate cu o sursă bisericească. O onomastică greșită se vede direct într-o notificare trimisă unui om real — e cel mai vizibil tip de eroare pe care îl poate face produsul.

### Regula tehnică
```php
// Push doar daca: is_verified = true SI confidence >= min_confidence_to_push
config('wishio.reminders.min_confidence_to_push')  // 0.75
```
Onomasticile neverificate pot fi **afișate** în aplicație și propuse spre confirmare, dar **nu generează notificări**.

### Cum se verifică

1. Procură un **calendar creștin ortodox** tipărit pentru anul în curs — se găsesc la orice biserică sau librărie religioasă din Chișinău. Cere unul pe stil nou și, dacă există, unul pe stil vechi.
2. Confruntă fiecare intrare din `name_days.php`: ziua, luna, denumirea sfântului.
3. Pentru fiecare verificată: `is_verified = true` și `source` = numele calendarului și anul.
4. **Începe cu cele 21 majore** — acoperă majoritatea contactelor. Restul pot aștepta.

### Ce merită atenție specială

| De verificat | De ce |
|---|---|
| Sfinții cu **mai multe date** în an (Ioan, Maria, Ana, Nicolae) | am inclus data principală; celelalte au încredere redusă, dar datele trebuie corecte |
| Denumirile complete ale sfinților, în RO și RU | apar în notificări; o formulare greșită se vede |
| Dacă varianta pe stil vechi e cea folosită efectiv în MD | regula +13 e corectă matematic; confirmă că e și cea practicată |
| Nume frecvente **fără** onomastică în date | Valentin, Victor, Igor, Oleg, Svetlana, Galina, Veronica, Diana, Denis, Artur, Iulian, Corina — merită adăugate |

### Extindere viitoare
- **România** (`country_code = 'RO'`): aceleași sărbători, doar stil nou. Efort mic, deblochează piața #2.
- **Catolici** (`calendar = 'catholic'`): minoritate în MD, dar există.

---

## 5. Cum se folosește în produs

```php
$match = app(ResolveNameDay::class)->best('Gheorghe Rusu', 'MD', 'orthodox_new');

$match->nameDay->day;              // 23
$match->nameDay->month;            // 4
$match->nameDay->saintName('ru');  // "Святой Георгий Победоносец"
$match->confidence;                // 1.0
$match->isDiminutive;              // false
$match->isConfident();             // true
```

`all()` returnează toate potrivirile, cea mai bună prima — util pentru ecranul de confirmare când un nume are mai multe sărbători (Maria: 15 august și 8 septembrie).

**Ecranul O7 din `docs/09`** prezintă rezultatele spre confirmare, niciodată ca fapt:

> *„Credem că Gheorghe își serbează onomastica pe 23 aprilie. Corect?"*
> ✓ Da · ✗ Altă dată · „Nu sărbătorește"

Un reminder greșit este mai rău decât niciun reminder. De aceea confirmarea nu e opțională.
