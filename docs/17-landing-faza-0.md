# 17 — Landing-ul Faza 0

> Implementarea pasului **P0.6** din `PLAN.md`. Landing + formular + fluxul concierge.
> **Temporar.** Se scoate după ce trecem porțile G0–G2, împreună cu tabelul `gift_requests`.

---

## 1. De ce în repo, contrar regulii din `docs/14 § 6`

`docs/14` spunea „nu scrii cod în repo-ul Wishio". Regula rămâne valabilă ca **intenție** — nu construim produsul înainte de validare — dar am revizuit-o pentru acest pas, cu un motiv concret:

Backend-ul are deja i18n RO/RU/EN funcțional, token-urile de brand și taxonomia de interese. A reconstrui landing-ul în altă parte ar fi însemnat să rescriu i18n-ul de la zero, pentru o pagină. Reutilizarea a costat câteva ore în loc de o zi.

**Ce protejează regula în continuare:** tot codul Faza 0 e izolat în `app/Domain/Validation/` + `LandingController` + un tabel, marcat ca temporar. Când validarea se termină, se șterg. Nu atinge niciun domeniu al produsului.

---

## 2. Ce s-a construit

| Componentă | Unde |
|---|---|
| Rute (landing, cerere, mulțumim, comutator limbă) | `backend/routes/web.php` |
| Controller | `backend/app/Http/Controllers/LandingController.php` |
| Model + migrare `gift_requests` | `app/Domain/Validation/Models/`, `database/migrations/2026_09_12_140000_*` |
| Pagini | `resources/views/landing/`, `resources/views/components/` |
| Copy în RO / RU / EN | `backend/lang/{ro,ru,en}/landing.php` |
| Unealtă concierge | `app/Console/Commands/GiftRequestsCommand.php` |
| Teste | `backend/tests/Feature/LandingTest.php` — 15 teste |

Design pe **aceleași token-uri ca aplicația mobilă** (`mobile/global.css` ↔ `resources/css/app.css`): brandul e consistent de la prima pagină publică.

---

## 3. Formularul

Cele 7 câmpuri din `docs/14 § 4`, plus consimțământ:

| Câmp | Obligatoriu | Rol în validare |
|---|---|---|
| Pentru cine (relația) | ✅ | intră în criteriile AI de la S7 |
| Vârsta aproximativă | — | idem |
| **Ce îi place** *(text liber)* | ✅ | **cel mai valoros lucru din toată Faza 0** |
| Bugetul | ✅ | validează benzile de preț din `docs/16` |
| Gen | — | indiciu pentru ranking |
| Ocazia + data | — | validează tipurile de ocazii |
| Contact | ✅ | livrarea; canalul se deduce automat |
| Consimțământ | ✅ | versionat, cu marcaj de timp |

Limba se ia din sesiune, deci nu e un câmp — utilizatorul a ales-o deja din comutator.

> Câmpul **„ce îi place"** devine corpusul de intrare pentru prompturile de la S7 (`docs/14 § 1`). Textul brut se păstrează exact cum a fost scris.

---

## 4. Privacy — aceleași reguli ca produsul

Tabelul e temporar, dar conține date personale reale: contactul solicitantului și descrierea unei **terțe persoane** care nu știe că e descrisă.

| Regulă | Implementare |
|---|---|
| Consimțământ explicit | bifă obligatorie; fără ea nu se salvează nimic — testat |
| Dovada consimțământului | `consent_version` + `consented_at` — ce text a acceptat și când |
| Fără IP în clar | `ip_hash` = HMAC-SHA256 cu cheia aplicației — testat |
| Limitarea abuzului | 5 cereri / 10 minute per IP — testat |
| Fără indexare | `noindex` cât timp suntem în Faza 0 — testat |
| Ștergere la cerere | textul de consimțământ o promite; se face manual, sunt 20 de înregistrări |

---

## 5. Fluxul concierge

```bash
php artisan wishio:requests            # toate cererile, cu interesele deduse automat
php artisan wishio:requests --pending  # doar cele fără răspuns
php artisan wishio:requests --answer=7 # notezi ce ai trimis și ce a răspuns
php artisan wishio:requests --stats    # porțile G0 și G1
```

Listarea rulează **taxonomia de interese (`docs/16`) pe fiecare cerere** și arată ce ar fi dedus produsul singur. Două foloase: e punctul de plecare al căutării tale manuale, și compari deducerea automată cu ce ai găsit tu — exact materialul de calibrare din `docs/16 § 5`.

`--stats` măsoară porțile din `docs/02 § 3` și, când timpul mediu de căutare trece de 30 de minute după 8 cereri livrate, semnalează că unealta V2 din `docs/14 § 3` devine justificată — pragul e cel pe care l-am stabilit acolo, nu unul inventat pe loc.

---

## 6. Două bug-uri prinse de teste

**Limba implicită era engleza, nu româna.** `Request::getPreferredLanguage()` din Symfony nu întoarce primul element al listei tale când lipsește `Accept-Language` — întoarce locale-ul implicit al lui Symfony, `en`. În producție, orice client care nu trimite headerul ar fi primit engleză, pe o aplicație unde româna e limba de bază. Middleware-ul verifică acum explicit că headerul există și e nevid.

Testul a fost greu de scris pentru că `Symfony\Request::create()` **injectează singur** `Accept-Language: en-us,en;q=0.5` — headerul trebuie golit explicit ca să reproduci o cerere reală fără el.

**Numerele de telefon nu erau recunoscute.** Detectarea canalului căuta 6 cifre consecutive, dar oamenii scriu `+373 69 123 456`. Acum cifrele se extrag înainte de numărare.

---

## 7. Previzualizare socială, favicon, analytics

### Imaginile Open Graph — una pe limbă

```bash
php artisan wishio:og-images     # public/og/{ro,ru,en}.png, 1200×630
```

Când postezi linkul într-un grup de Facebook sau pe Telegram, previzualizarea este primul lucru pe care îl văd oamenii. Fără ea linkul arată rupt și conversia scade — contează direct pentru P0.7. Pagina servește automat imaginea în limba curentă, deci un link postat într-un grup rusofon arată în rusă.

> **Capcană prinsă la generare:** `wordwrap()` din PHP numără **octeți, nu caractere**. Chirilicul are 2 octeți pe literă, așa că titlul rusesc se rupea în patru rânduri și intra peste subtitlu. Împachetarea e acum multibyte-safe, iar subtitlul se poziționează sub titlu, nu la o coordonată fixă.

### Favicon

`public/favicon.svg` + PNG-uri derivate (32, 180, 512). Construit doar din forme simple — Imagick nu randează corect `<path>` cu contur, iar prima versiune ieșea ca două dreptunghiuri albe în loc de o cutie de cadou.

### Analytics

Snippetul PostHog se încarcă **doar dacă există cheia**; pagina funcționează identic fără ea. Evenimente, conform `docs/07 § 3`:

| Eveniment | Când | Ce măsoară |
|---|---|---|
| `landing_viewed` | la încărcare | **G0**, cu `source` din `?src=` |
| `language_selected` | la comutator | distribuția RO/RU/EN (`docs/07 § 5`) |
| `form_started` | la prima tastare | câți încep față de câți trimit |
| `request_submitted` | pe pagina de mulțumire | conversia finală |

`autocapture` este oprit deliberat: colectăm doar ce am definit, nu tot ce se întâmplă pe pagină.

## 8. Ce mai trebuie făcut înainte de lansare

| # | Ce | Efort |
|---|---|---|
| 1 | `POSTHOG_KEY` în `.env` | 10 min |
| 2 | Hosting + `wishio.md` + TLS | 2 h |
| 3 | Recitește copy-ul RO și RU cu voce tare | 30 min |
| 4 | Un test pe telefon real, în toate trei limbile | 20 min |
| 5 | `?src=` pe fiecare link distribuit | 0 |

Punctul 5 e gratuit și îți spune exact ce canal a mers: `wishio.md/?src=fb_mame`, `?src=telegram`, `?src=reddit`. Se salvează în `source` **și** ajunge în `landing_viewed`. Răspunde la B7 fără niciun efort.
