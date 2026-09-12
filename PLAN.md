# PLAN.md — Planul complet de execuție

**Wishio** — asistent de ocazii și cadouri. RO (bază) · RU · EN
Stack: React Native + Expo + NativeWind v5 · Laravel 12 · MySQL 8.4 / MariaDB · Redis

---

## Cum se citește

Fiecare pas are un **ID** (`P0.3`, `S4.2`), un livrabil verificabil și un criteriu de „gata”.
Mergem secvențial. Un pas nu se consideră terminat fără criteriul lui.

**Ritmul confirmat: 15 h/săptămână** (`docs/00 § D-010`). Sprinturile de mai jos sunt numerotate ca săptămâni de lucru; la 15 h/săpt., **MVP-ul durează ~16 săptămâni calendaristice**, nu 12. Numerotarea S1–S12 rămâne ca unitate de conținut, nu de calendar.

Legendă: 🔴 blocant · 🟠 important · 🟡 poate aluneca · ✅ făcut

---

## Starea curentă

| | |
|---|---|
| ✅ | Documentație completă de produs, strategie, domeniu, arhitectură, privacy, metrici (`docs/01`–`08`) |
| ✅ | Monorepo inițializat, git + remote `github.com/Liemacs/wishio` |
| ✅ | Laravel 12 scaffoldat, MySQL/MariaDB cu `utf8mb4_unicode_ci` (verificat: `Ștefan` = `stefan`) |
| ✅ | Structură pe domenii `app/Domain/*`, `config/wishio.php`, `.env` |
| ✅ | Expo SDK 57 + NativeWind v5 + Tailwind v4, token-uri Wishio, **build web verificat** |
| ✅ | `docker/compose.yaml` (MySQL 8.4 + Redis + Mailpit), `Makefile`, `.gitignore` |
| ✅ | i18n RO/RU/EN cap-coadă: `/api/v1/ping` localizat, plural rusesc în 3 forme verificat |
| ✅ | Migrări rulate pe MariaDB (XAMPP), bază `wishio` creată |
| ✅ | **Resolver de onomastici** — 49 sărbători × 2 calendare, 658 aliasuri (`docs/15`) |
| ✅ | **Taxonomia de interese** — 15 grupuri, 80 interese, 1.164 cuvinte-cheie RO/RU/EN (`docs/16`) |
| ✅ | **Landing Faza 0** — RO/RU/EN, formular cu consimțământ versionat, unealtă concierge (`docs/17`) |
| ✅ | **Domeniul People** — ierarhia de încredere, override-uri definitive, note criptate |
| ✅ | **API v1** — persoane, interese, autorizare pe proprietar |
| ✅ | **Ecrane mobile** — login, listă persoane, detaliu, adăugare, selector de interese |
| ✅ | **Import contacte + ocazii** — onomastici deduse, confirmare, re-sync fără duplicate |
| ✅ | **Remindere** — planificare, trimitere, preferințe, ecran principal |
| ✅ | **Sărbători** — 9 pentru MD, Paștele ortodox calculat, public per sărbătoare |
| ✅ | **Digest săptămânal** — email în RO/RU/EN, cu dezabonare semnată |
| ✅ | **Catalog** — produs/oferte, deduplicare, scor de cadou, căutare, clickuri |
| ✅ | **Motorul de recomandări** — criterii, filtre, scoring, diversificare, anti-repetare |
| ✅ | **Evaluare + CI** — 30 de profiluri, paritate traduceri, formatare |
| ✅ | **Ecranele de recomandare** — buget, consimțământ AI, rezultate, magazin |
| ✅ | **Pagini publice `@slug`** — bucla virală, cu consimțământ și ștergere fără cont |
| ✅ | **Ecranul de profil** — link de partajat, listă de dorințe, vizibilitate, setări |
| ✅ | 238 teste verzi · bundle iOS verificat **cu stilurile compilate** |
| ✅ | Pest instalat; testele rulează pe MySQL, nu SQLite (depind de colație) |
| ⬜ | Tot restul |

### Blocante de mediu — de rezolvat înainte de S1

| ID | Problemă | Acțiune |
|---|---|---|
| **E1** 🟡 | **Spațiu în calea proiectului** (`/Volumes/T7 1/`). **Nu afectează** Expo Go sau EAS cloud builds — verificat, bundle-ul se construiește. Risc doar la build-uri native **locale** (`expo prebuild`, `run:android`, `eas build --local`), unde Gradle/CocoaPods au probleme cu spațiile. | Nimic acum. Dacă ajungi la build-uri native locale, mută proiectul într-o cale fără spații. Symlink-ul nu ajută — Metro rezolvă calea reală. |
| **E2** 🟠 | Cache npm cu fișiere root-owned | `sudo chown -R $(id -u):$(id -g) ~/.npm` |
| **E3** 🟠 | Redis nu e instalat (MariaDB merge prin XAMPP) | `brew install redis` sau OrbStack/Docker → `make up`. Necesar la S5 (cozi). |
| **E4** 🟠 | PHP activ este 8.2; recomandat 8.3 | `brew link --overwrite php@8.3` (php@8.3 e deja instalat) |

---

# FAZA 0 — Validare (AMÂNATĂ — vezi `docs/00 § D-013`)

> Landing-ul e construit și funcțional. Execuția validării s-a amânat; pașii de mai jos rămân valabili pentru momentul în care se reia.

## Pașii originali

> Scopul: să afli dacă cineva vrea „ajută-mă să aleg” **înainte** de 3 luni de cod.
> Ieftin, rapid, și poate salva jumătate de an. Detalii în `docs/03-scop.md`.

| ID | Pas | Efort | Gata când |
|---|---|---|---|
| **P0.1** 🔴 | **Răspunde-ți în scris** la A1–A3 din `docs/08`: ore/săptămână, singur sau cu echipă, buget | 1 h | Sunt scrise în `docs/00-decizii-luate.md` |
| **P0.2** 🔴 | **Testul contactelor.** 10 telefoane reale, numără câte contacte au ziua de naștere completată. Notează procentul. | 1 h | Cifra e scrisă. Sub 5% → onboarding-ul se construiește pe onomastici + link |
| **P0.3** ✅ | ~~Contact Magaziner~~ — **acceptat**. Acum: trimite specificația din `docs/12` și formalizează înțelegerea | 2 h | Ai specificația API confirmată în scris |
| **P0.4** 🟠 | Verifică nume + domeniu (`.md`/`.com`/`.app`) + App Store + marcă AGEPI | 2 h | Numele e confirmat sau schimbat |
| **P0.5** 🟠 | Deschide **Apple Developer** (99 USD/an) și **Google Play** (25 USD) — activarea durează | 1 h | Conturile sunt în curs de activare |
| **P0.6** ✅ | ~~Landing RO/RU/EN + formular + analytics~~ — **construit și testat** (`docs/17`): previzualizare socială pe limbi, favicon, evenimente PostHog. Rămâne: cheia PostHog, hosting, `wishio.md` | 2 h | E live pe wishio.md |
| **P0.7** 🔴 | **Distribuție:** grupuri Facebook MD, Telegram, colegi, cunoscuți | 3 h | 150+ vizitatori |
| **P0.8** 🔴 | **Concierge: 20 de recomandări manuale.** Caută de mână pe Magaziner/Darwin/Bomba/Ultra. Trimite 5 carduri în limba cerută. Întreabă „ai cumpăra?”. **Notează tot** — e corpusul pentru prompt | 10 zile, ~1 h/zi | 20 livrate, feedback notat |
| **P0.9** 🔴 | **Discuții cu 5 magazine + 2 restaurante.** Întrebarea: „ai plăti 2.000 MDL/lună pentru 500 de clickuri de la oameni cu buget declarat?” | 6 h | 7 răspunsuri, cu cifre |
| **P0.10** ✅ | ~~C1 tabelul de onomastici~~ — **făcut**: 49 sărbători, 658 aliasuri, resolver + 16 teste. Rămâne **verificarea datelor** cu un calendar bisericesc (`docs/15 § 4`) | — | `is_verified = true` pe cele 21 majore |
| **P0.11** ✅ | ~~C2 taxonomia de interese~~ — **făcut**: 15 grupuri, 80 interese, 1.164 cuvinte-cheie, potrivire din text liber + 11 teste. Rămâne maparea pe categoriile Magaziner (`docs/16 § 5`) | — | mapare completă |

### Ce măsori la final

| Reper | Prag | Dacă e sub prag |
|---|---|---|
| **G0** | 150+ leaduri la cost ≈0 | mesajul nu prinde — reformulează |
| **G1** | din 20 de recomandări, **10+** spun „asta chiar aș cumpăra”, **5+** dau click | recomandările sau catalogul au nevoie de lucru |
| **G2** | **2 din 7** comercianți spun „da, aș plăti”, cu o cifră | e utilitate, nu business — reconsideră modelul |

Cifrele îți spun unde să insiști.

---

# MVP — 12 sprinturi (~16 săptămâni la 15 h/săpt.)

## S1 · Fundație (săptămâna 1)

| ID | Pas | Gata când |
|---|---|---|
| S1.1 | Rezolvă E2–E4 (E1 nu blochează) | Redis pornit, `php -v` arată 8.3 |
| S1.2 | `make setup`; migrările rulează pe MySQL | `php artisan migrate` trece |
| S1.3 | **Schema de evenimente analytics** (PostHog) — `docs/07 § 3`, **înainte de orice feature** | un eveniment de test ajunge în PostHog |
| S1.4 | i18n backend: middleware `Accept-Language` → `users.locale` → RO; `lang/{ro,ru,en}` | `/api/v1/ping` răspunde localizat în toate trei |
| S1.5 | i18n mobile: i18next + expo-localization, comutator de limbă, `ro/ru/en.json` | ecran de test comută corect, plural RU corect |
| S1.6 | **Componente de compunere** în `src/components/ui/` peste stack-ul din `docs/05 § 1.1`: Button, Card, OccasionCard, PersonRow, ProductCard, EmptyState, Section, Sheet | fiecare randează în RO/RU/EN, cu animație și haptic |
| S1.7 | expo-router + structura `app/`; TanStack Query + Zustand + client API | navigare între 2 ecrane, un fetch reușit |
| S1.8 🟠 | Auth: **Sanctum instalat**; rămân Apple, Google, email OTP | te loghezi din app și primești token |
| S1.9 ✅ | ~~CI GitHub Actions~~ — Pint, teste pe MySQL, paritate i18n, evaluare, `tsc --noEmit` |
| S1.10 | OpenAPI 3.1 + generare tipuri TS | tipurile se generează din spec |

**Gata când:** te loghezi din aplicație, schimbi limba, primești răspuns localizat, CI e verde.

## S2 · Date de bază (săptămâna 2)

| ID | Pas | Gata când |
|---|---|---|
| S2.1 ✅ | ~~C2 Taxonomia~~ — implementată. La S2: **maparea pe categoriile Magaziner** și prunarea intereselor fără produse (`docs/16 § 5`) | fiecare interes are ≥10 produse sau iese din selector |
| S2.2 ✅ | ~~C1 Onomastici~~ — implementat și testat. La S2: doar **verificarea datelor** și extinderea cu numele frecvente care lipsesc (`docs/15 § 4`) | `is_verified = true` pe cele majore |
| S2.3 | **C10 — Sărbători MD** cu relevanță pentru cadouri (8 Martie, 1 Iunie, Crăciun, Paște, 1 Sep, 5 Oct), cu reguli de dată | `holidays` populat |
| S2.4 | Normalizare telefon E.164 (libphonenumber) + HMAC cu pepper | teste: `069123456` = `+373 69 123 456` = `00373...` |
| S2.5 | Normalizare nume insensibilă la diacritice + transliterare RU→RO | `Ștefan` = `stefan` = `Штефан` |

## S3 · Persoane (săptămâna 3)

| ID | Pas |
|---|---|
| S3.1 ✅ | ~~Migrări `people`, `person_field_sources`, `person_interests`, `person_avoids`~~ |
| S3.2 ✅ | ~~CRUD Person prin API~~ — `/api/v1/people`, cu politică de acces și 16 teste |
| S3.3 ✅ | ~~**Ierarhia de trust + override-uri**~~ — `FieldSource` + `WritePersonField`, 11 teste |
| S3.4 ✅ | ~~Ecrane: listă cu căutare, detaliu, adăugare, ștergere~~ |
| S3.5 | Selector de interese din taxonomie, în limba userului |

**Gata când:** adaugi manual o persoană cu interese și un override manual nu e suprascris de un sync simulat.

## S4 · Contacts & onomastici (săptămâna 4) ← *săptămâna cu cel mai mare risc*

| ID | Pas |
|---|---|
| S4.1 ✅ | ~~Ecran explicativ înainte de promptul nativ~~ |
| S4.2 ✅ | ~~Citire contacte~~ — doar nume + zi de naștere. **Fără numere de telefon deloc** (`docs/00 § D-017`) |
| S4.3 ✅ | ~~Selecție explicită~~ — cu pre-bifarea celor care au deja ziua completată |
| S4.4 ✅ | ~~Name-day resolver + ecran de confirmare~~ — una câte una, cu corectarea datei |
| S4.5 ✅ | ~~Deduplicare, re-sync, detectarea modificărilor~~ — pe `device_contact_id` |
| S4.6 ✅ | ~~Flow funcțional fără permisiune~~ — refuzul duce la adăugare manuală |
| S4.7 ✅ | ~~Telemetrie R1~~ — `stats.contacts_with_birthday` trimis agregat la import |

**Gata când:** un telefon real cu 200+ de contacte produce un calendar plin, chiar dacă doar 5 au ziua de naștere.

## S5 · Ocazii & remindere (săptămâna 5)

| ID | Pas |
|---|---|
| S5.1 ✅ | ~~`occasions` + sărbători~~ — 9 sărbători MD, Paștele calculat; rămân ocaziile personalizate |
| S5.2 ✅ | ~~Planificator~~ — orizont de 35 de zile, per fus orar, quiet hours, anti-spam |
| S5.3 ✅ | ~~Push prin Expo~~ — driver comutabil, limba fixată la planificare, rutare la apăsare |
| S5.4 ✅ | ~~Promptul de push după aha moment~~ |
| S5.5 ✅ | ~~Digest săptămânal~~ — luni 9 dimineața în fusul fiecăruia, 3 limbi, dezabonare într-un click |
| S5.6 ✅ | ~~Home: „la cine te gândești azi?”~~ |
| S5.7 | Widget iOS/Android cu următoarea ocazie (retenție fără push) |

**Gata când:** un push programat ajunge pe device fizic, în RU, la ora corectă, fără să încalce quiet hours.

## S6 · Catalog (săptămâna 6)

| ID | Pas |
|---|---|
| S6.1 ✅ | ~~Migrări catalog~~ — `products` + `offers`, 22 categorii cu scor de bază |
| S6.2 ✅ | ~~`CatalogAdapter`~~ + `ManualCatalog`; `MagazinerCatalog` când apare API-ul |
| S6.3 ✅ | ~~Stratul de curatare~~ — filtru „nu e cadou” + `gift_score` pe reguli; corecția manuală rămâne pentru catalogul real |
| S6.4 ✅ | ~~Căutare + filtre~~ — text, buget, interese |
| S6.5 ✅ | ~~Card de produs + click spre magazin cu tracking~~ |
| S6.6 ✅ | ~~Deduplicare + „vezi la alte N magazine”~~ |

## S7–S8 · Recomandări (săptămânile 7–8)

| ID | Pas |
|---|---|
| S7.1 ✅ | ~~`AiProvider` + contract~~ — `RuleBasedAiProvider` implicit; furnizorul LLM se adaugă fără schimbări în pipeline |
| S7.2 ✅ | ~~Validare strictă~~ — codurile inexistente se aruncă, nu se aproximează |
| S7.3 ✅ | ~~Pipeline complet~~ — cu descompunerea scorului salvată, ca rezultatele să fie depanabile |
| S7.4 ✅ | ~~Anti-repetare + excluderi~~ |
| S7.5 ✅ | ~~Consimțământ AI + rută fără AI~~ — backend și ecran |
| S7.6 ✅ | ~~Generare asincronă~~ — 202 Accepted, clientul interoghează starea |
| S8.1 ✅ | ~~Set de evaluare, 30 de profiluri~~ — rulat în CI, 30/30, p95 15 ms |
| S8.2 ✅ | ~~Frază liberă → interese~~ — API și ecranul P5 |
| S8.3 ✅ | ~~Buget de cost + comutare pe ruta fără AI la depășire~~ |

**Gata când:** pentru 8 din 10 profiluri de test, ≥3 din 5 sugestii sunt plauzibile, în RO, RU și EN.

## S9 · Profil propriu & link public (săptămâna 9) ← *motorul de creștere*

| ID | Pas |
|---|---|
| S9.1 ✅ | ~~Profilul meu + lista de dorințe~~ — API și ecran |
| S9.2 | Vizibilitate per câmp, inclusiv `signal_only` (`docs/04 § 6`) |
| S9.3 ✅ | ~~Pagini publice `@slug`~~ — Blade, RO/RU/EN, `noindex` implicit, slug cu sufix aleator |
| S9.4 ✅ | ~~Formular + consimțământ versionat + rate limit + ștergere fără cont~~ |
| S9.5 ✅ | ~~Prompt de instalare + eveniment `profile_submission`~~ |
| S9.6 ✅ | ~~Deep link `@slug` → aplicație~~ — config + fișiere de asociere; cer un build semnat ca să funcționeze |

## S10 · Istoric cadouri & idei (săptămâna 10)

| ID | Pas |
|---|---|
| S10.1 | `gift_ideas`, `gift_history`; salvare, „ales”, „cumpărat”, „oferit” |
| S10.2 | Timeline per persoană |
| S10.3 | Filtrare anti-repetare activată în recomandări |

## S11 · Conformitate & polish (săptămâna 11)

| ID | Pas |
|---|---|
| S11.1 🔴 | **Ștergere cont + export date**, în aplicație (obligatoriu Apple) |
| S11.2 🔴 | **C11 — Privacy Policy + ToS în RO/RU/EN**, la URL stabil |
| S11.3 🔴 | **C12 — DPIA + registrul prelucrărilor** (Legea 195/2024, în vigoare din 23 aug 2026) |
| S11.4 | Privacy Nutrition Label (Apple) + Data Safety (Google) |
| S11.5 | Stări goale, erori, offline, skeletons, animații |
| S11.6 🔴 | **Audit de traduceri**: zero stringuri hardcodate, zero fallback vizibil, testat cu RU (texte mai lungi) |
| S11.7 | Sentry cu scrubbing PII; test real de restaurare backup |
| S11.8 | Test: aplicația **fără** Contacts și **fără** push — complet utilizabilă |

## S12 · Lansare (săptămâna 12)

| ID | Pas |
|---|---|
| S12.1 | **C13 — materiale store în 3 limbi**: icon, screenshot-uri, descriere |
| S12.2 | Cont demo pentru App Review, cu date populate, instrucțiuni în EN |
| S12.3 | TestFlight / Internal Testing cu 20–30 de oameni reali |
| S12.4 | Submit — **buffer de 2 săptămâni** pentru respingeri (5.1.2 e probabilă la prima încercare) |
| S12.5 | Lansare: grupuri FB MD, Telegram, Reddit local, PR |

---

# După MVP

| Perioadă | Focus | Poartă |
|---|---|---|
| Săpt. 13–16 | Analiza G3–G5, reparat onboarding-ul, catalog extins | G3 activare >60%, G4 push >35%, G5 click >15% |
| Săpt. 17–22 | **v1.1 Experiențe** — 30–60 locații Chișinău în 3 limbi | ticket mediu mai mare |
| Săpt. 23–30 | **v2** — merchant dashboard, sponsorizări, group gifting **fără** plăți | primul venit real |
| Luna 9+ | **România** — 19 mil., afiliere matură (2Performant, Profitshare, eMAG) | G6 D30>25%, G7 K>0.25 |

---

# Camp de mine

| Capcană | Contramăsură |
|---|---|
| Spațiul din calea proiectului | **E1, rezolvă-l acum**, nu după primul build nativ eșuat |
| Contacts sync „merge la mine pe telefon” | 5 telefoane reale, 500+ contacte, ambele platforme, din S4 |
| Push-urile nu ajung | device fizic din S5; simulatorul minte |
| Traducerile lăsate la final | DoD le include; S11.6 e verificare, nu muncă |
| Catalogul „se face într-o zi” | sunt 3–4 zile de muncă plictisitoare — programează-le |
| Promptul se ajustează la nesfârșit | eval set în CI; dacă scorul nu crește, oprește-te |
| App Review respinge 5.1.2 | citește `docs/06` **înainte** de S4 |
| Scope creep | `docs/03 § Anti-scop` |
| Construiești 3 luni fără să vorbești cu un comerciant | P0.9 e obligatoriu |
