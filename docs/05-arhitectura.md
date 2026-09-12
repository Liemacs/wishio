# 05 — Arhitectură tehnică

> Stack confirmat: **React Native + Expo**, **Laravel**, **MySQL/MariaDB**.
> Documentul justifică alegerile, marchează unde au consecințe reale și ce compensăm.
> Layerul de UI: vezi `docs/00 § D-006`. Baza de date în dev: vezi § 2 și `docs/00 § D-007`.

---

## 1. Stack

| Strat | Alegere | Note |
|---|---|---|
| Mobile | **React Native + Expo (SDK 54+) + TypeScript** | Contacts, push, deep links, widget-uri, un codebase iOS+Android. EAS Build elimină pipeline-ul nativ. |
| UI | **NativeWind v5 + Tailwind v4**, design system propriu în `src/components/ui/` | gluestack-ui nu are încă linie stabilă pentru SDK 57 — vezi `docs/00 § D-006`. Componentele sunt copy-paste în repo, ca la gluestack/shadcn: control total, migrare incrementală ulterioară. |
| Navigație | **expo-router** | rutare pe fișiere, deep links gratuit (esențial pentru `@slug` → app) |
| State | **Zustand** (UI) + **TanStack Query** (server state) | cache, retry, offline, invalidare |
| i18n mobile | **i18next + expo-localization** | RO sursă, fallback RO, plural RU corect |
| Backend | **Laravel 12 / PHP 8.3** | competența ta = viteză maximă. Nu experimenta aici. |
| DB | **MySQL 8.4 LTS** în producție · **MariaDB 10.4** (XAMPP) în dev | vezi §2 — colația se alege portabil, ca să meargă pe ambele |
| Cache / cozi | **Redis + Laravel Horizon** | remindere, sync catalog, generare recomandări |
| Căutare | MVP: SQL. v1.1: **Meilisearch** | vezi §3 |
| API | **REST + OpenAPI 3.1** | tipuri TS generate pentru mobile |
| Auth | **Laravel Sanctum** + Sign in with Apple + Google + email OTP | Apple e obligatoriu pe iOS dacă ai orice alt social login |
| Push | **expo-notifications** → APNs/FCM | un singur API |
| AI | interfață `AiProvider`, implementări interschimbabile | nu te legi de un vendor |
| Web public | **Laravel + Inertia + Vue 3** | landing, pagini `@slug`, merchant dashboard — SSR, indexabil, hreflang |
| Analytics | **PostHog** | funnels, cohorte, feature flags |
| Erori | **Sentry** | mobile + backend, cu scrubbing PII |
| Infra | Docker + Nginx, VPS în UE (Hetzner) | date în UE simplifică Legea 195/2024 |
| CI/CD | GitHub Actions + EAS | lint, teste, build, deploy |

### De ce NativeWind + design system propriu
- **Token-uri CSS-first** (Tailwind v4): paleta și formele se definesc o dată, în `global.css`, și sunt disponibile în toată aplicația.
- **Componentele stau în repo** (`src/components/ui/`) — exact modelul gluestack/shadcn. Le modifici fără fork, fără să lupți cu o bibliotecă.
- **Universal** — aceleași clase alimentează și web-ul Inertia.
- **Zero dependențe alpha în runtime.**

**Capcane de știut din prima zi:**
- NativeWind v5 este preview; fixează versiunea exactă în `package.json` și nu o actualiza fără să rulezi build-ul.
- `@legendapp/motion` (și orice pachet cu peer `nativewind >=4.0.0`) **nu se instalează** peste un prerelease — npm nu potrivește prerelease-uri la range-uri simple.
- Token-urile se definesc în `@theme` în `global.css`, **nu** în `tailwind.config.js` (care nu mai există în Tailwind v4).
- Textele **RU sunt cu 10–15% mai lungi decât RO** — toate componentele se testează în RU, nu în EN.

---

## 2. Baza de date — consecințe și compensări

Stack-ul e MySQL. În dezvoltare rulează **MariaDB 10.4.28 prin XAMPP**, ceea ce e util de știut explicit, pentru că nu sunt interschimbabile.

### Alegerea colației — verificată empiric, nu presupusă

Potrivirea numelor românești cu diacritice este **critică** pentru name-day resolver (`Ștefan` trebuie să potrivească `stefan`). Testat direct pe MariaDB 10.4:

| Colație | `'Ștefan' = 'stefan'` | Verdict |
|---|---|---|
| `utf8mb4_unicode_ci` | ✅ 1 | **aleasă** — portabilă MariaDB + MySQL 8 |
| `utf8mb4_general_ci` | ✅ 1 | funcționează, dar colație legacy, sortare mai slabă |
| `utf8mb4_romanian_ci` | ❌ 0 | **capcană** — colația „românească" tratează diacriticele ca litere distincte și ar rupe exact funcția pentru care ai vrea-o |
| `utf8mb4_0900_ai_ci` | — | **nu există în MariaDB**; e doar MySQL 8 |

```
DB_COLLATION=utf8mb4_unicode_ci
```
Configurabil prin env, ca să poți urca la `utf8mb4_0900_ai_ci` dacă producția e MySQL 8 pur.

### Diferențe MariaDB 10.4 vs MySQL 8.4 care ne afectează

| Aspect | MariaDB 10.4 | MySQL 8.4 | Ce facem |
|---|---|---|---|
| Tip `JSON` | alias peste `LONGTEXT` + CHECK | tip nativ, validat, indexabil | traducerile merg pe ambele; pentru câmpurile filtrate des folosim **coloane generate + index**, care există în ambele |
| `utf8mb4_0900_*` | absent | prezent | colație portabilă (mai sus) |
| Suport | **EOL din iunie 2024** | LTS până în 2032 | vezi mai jos |
| Vectori | 11.7+ | 9.x | irelevant — nu folosim vectori la MVP (§3) |

### Recomandare

MariaDB 10.4 este **bună ca să începi azi** și migrările Laravel rulează pe ea fără modificări. Dar este **end-of-life din iunie 2024** — fără patch-uri de securitate. Pentru un produs care prelucrează date personale sub Legea 195/2024, nu o duce în producție.

**Plan:** dezvoltă pe XAMPP dacă îți e comod; rulează **MySQL 8.4** în CI și în producție (`docker/compose.yaml` îl pornește deja). Colația portabilă face ca ambele să se comporte la fel pentru cazurile noastre.

### Convenții obligatorii
- `contact_hash` → `CHAR(64)`, colație `utf8mb4_bin` (comparație exactă), index unic compus cu `user_id`
- slug-urile → `utf8mb4_bin`
- sume de bani → `DECIMAL(12,2)`, **niciodată** `FLOAT`
- toate datele stocate UTC; conversia la fusul userului se face în aplicație
- atenție la limita de 3072 bytes pe index cu utf8mb4 → prefixe pe coloane text lungi
- traduceri: `$table->json('translations')` + coloane generate unde ai nevoie de index

---

## 3. Recommendation Engine — anti-halucinație

Regula ridicată la lege: **AI-ul nu produce niciodată un produs. Produce criterii și explicații. Produsele vin exclusiv din baza noastră.**

```
Person profile + occasion + budget + locale
            │
            ▼
   [1] LLM: extrage CRITERII        ← singurul apel LLM din flow
            │  JSON validat pe schemă; doar leaf-uri din taxonomie
            ▼
   [2] FILTRE DETERMINISTE (SQL)
       • preț în buget          • în stoc
       • țara/orașul userului   • exclude gift_history (anti-repetare)
       • exclude person_avoids  • exclude respinse anterior
            │
            ▼
   [3] SCORING — determinist, fără AI, fără embeddings
       score = 0.45 · overlap_interese     (JOIN product_interests × person_interests,
                                            ponderat cu weight × confidence)
             + 0.20 · potrivire_buget      (distanța față de mijlocul intervalului)
             + 0.15 · gift_score           (cât de „de cadou” e, editorial, 1–5)
             + 0.10 · prospețime / stoc
             + 0.10 · boost_sponsorizat    (marcat vizibil ca sponsorizat)
            │
            ▼
   [4] DIVERSIFICARE
       max 2 produse din aceeași categorie, max 2 de la același magazin
            │
            ▼
   [5] LLM: motiv scurt per produs, ÎN LIMBA USERULUI
       primește doar titlurile selectate + interesele; nu poate adăuga produse
            │
            ▼
   [6] VALIDARE: orice item care nu există în setul trimis → aruncat
```

**De ce nu ai nevoie de embeddings la MVP:** cu 300–500 de produse taguite manual pe 60–80 de interese, un `JOIN` cu ponderi dă rezultate **mai bune și mai explicabile** decât similaritatea vectorială pe un catalog mic. Embeddings devin utile abia peste ~10.000 de produse needitate. Atunci adaugi Meilisearch, nu schimbi baza de date.

**Consecințe:** cost ≈ două apeluri scurte de LLM (fracțiuni de cent), rezultate reproductibile și debug-abile, catalogul se schimbă fără reantrenare, imposibil să apară un produs inexistent.

### Contractul de criterii (fix)
```json
{
  "interests":        ["audio", "motorsport"],
  "avoid_interests":  ["apparel"],
  "price_range":      { "min": 800, "max": 1500, "currency": "MDL" },
  "gift_style":       ["practical", "experience"],
  "recipient": { "gender": "male", "age_bracket": "25-34", "relationship": "friend" },
  "experience_types": ["karting", "restaurant"],
  "confidence": 0.82
}
```
Validat cu JSON Schema. Orice valoare din afara taxonomiei se aruncă, nu se „repară”.

### Set de evaluare (obligatoriu)
30 de profiluri fictive + rezultate așteptate, rulate în CI la fiecare modificare de prompt.
Metrici: `relevanță@5`, `% în buget`, `% duplicate cu gift_history`, `cost mediu`, `latență p95`, în toate trei limbile.

---

## 4. Catalog — adaptoare

```php
interface CatalogAdapter {
    public function sync(): CatalogSyncResult;   // idempotent
    public function supports(string $country): bool;
}
```

| Implementare | Când | Sursă |
|---|---|---|
| `ManualCatalog` | **MVP** | seed curat, 300–500 produse, taguite manual |
| `GoogleFeedCatalog` | plan B | feed XML Google Merchant direct de la magazine |
| `MagazinerCatalog` | dacă se semnează parteneriatul | feed/export agreat |
| `ExperienceCatalog` | v1.1 | manual, 30–60 locații Chișinău |

Sync pe cron, în cozi, cu `last_seen_at`. Produsele nevăzute 7 zile → `in_stock = false`, nu șterse.

**`gift_score` este editorial, nu calculat.** Un router și o pereche de căști pot avea același preț și aceeași categorie, dar unul nu e cadou. Acest scor manual, pe 400 de produse, valorează mai mult decât orice model.

---

## 5. AI — abstractizare și conformitate

```php
interface AiProvider {
    public function extractPersonTraits(string $freeText, string $locale): PersonTraits;
    public function generateGiftCriteria(PersonContext $ctx): GiftCriteria;
    public function explainRecommendations(array $items, PersonContext $ctx, string $locale): array;
    public function generateGreeting(PersonContext $ctx, string $locale, string $tone): string;
}
```

**Reguli de conformitate (vezi `docs/06-privacy-legal.md`):**
- context **pseudonimizat**: „prieten, bărbat, 27 ani, interese [...]”, niciodată nume/telefon/email
- Apple cere, din 13 noiembrie 2025, dezvăluire clară + permisiune explicită înainte de a trimite date personale către AI terț → ecran dedicat de consimțământ
- aplicația funcționează și **fără** acest consimțământ (recomandări doar pe filtre, fără explicații generate)
- notele libere despre persoane pot conține date sensibile → **nu se trimit spre AI la MVP**
- zero-retention / no-training, contractual

---

## 6. i18n — RO bază, RU și EN la paritate

**Nicio funcționalitate nu e terminată fără traduceri.** Definition of Done le include.

| Suprafață | Cum |
|---|---|
| UI mobile | `i18next`, `mobile/src/i18n/{ro,ru,en}.json`, RO = sursă |
| API | `Accept-Language` → fallback `users.locale` → fallback RO |
| Conținut de bază (categorii, interese, sărbători, onomastici, experiențe) | coloană `JSON translations` în MySQL, seed în toate trei |
| Emailuri și push | șabloane per locale; `notifications.locale` fixat **la programare**, nu la trimitere |
| Texte AI | generate direct în limba userului, nu traduse după |
| Pagini publice `@slug` | detectare + comutator; `hreflang` ro/ru/en + `x-default` = ro |
| Formate | `Intl` pentru date, numere, valută — zero string-uri hardcodate |
| Nume de persoane | **nu se traduc niciodată**; transliterare doar pentru name-day matching |

**Capcane RU:** plural cu 3 forme (1 / 2–4 / 5+ → *„через 1 день / 2 дня / 5 дней”*); genul verbului la trecut („добавил/добавила”) — evită construcțiile respective în copy; texte cu ~10–15% mai lungi.
**Capcană RO:** diacriticele. Normalizarea pentru căutare și name-day matching trebuie insensibilă la diacritice — `utf8mb4_0900_ai_ci` o face nativ în MySQL.

---

## 7. Cozi și joburi

| Job | Cadență | Rol |
|---|---|---|
| `ScheduleOccasionReminders` | zilnic 02:00 | ce remindere se trimit, per fus orar |
| `SendReminderNotification` | programat | push/email în locale-ul userului, cu quiet hours |
| `SyncCatalog` | 6h | adaptorul activ |
| `GenerateRecommendations` | la cerere | **niciodată sincron** — UI arată „căutăm idei...” |
| `ResolveNameDays` | la import + nightly | onomastici din prenume |
| `SendWeeklyDigest` | luni 09:00 local | fallback de retenție |
| `PurgeStaleData` | zilnic | retenție conform politicii |

**Anti-spam:** max 4 notificări per ocazie, max 2 push/zi per user, deloc în quiet hours, deloc pentru ocazii neconfirmate sub prag de confidence.

---

## 8. Structura monorepo

```
wishio/
├── backend/                  Laravel 12
│   ├── app/
│   │   ├── Domain/           People, Occasions, Reminders, Catalog,
│   │   │                     Recommendations, Identity, Profiles, Merchants
│   │   ├── Support/          Ai/, Localization/, PhoneNumbers/
│   │   └── Http/Api/V1/
│   ├── database/seeders/     name_days, holidays, interests, catalog
│   ├── lang/{ro,ru,en}/
│   ├── resources/js/         Inertia + Vue (web public)
│   └── tests/
├── mobile/                   Expo + RN + NativeWind
│   ├── app/                  expo-router
│   ├── src/
│   │   ├── components/ui/    design system propriu (copy-paste, pe NativeWind)
│   │   ├── features/         people, occasions, recommendations, profile
│   │   ├── i18n/             ro.json ru.json en.json
│   │   ├── api/              client generat din OpenAPI
│   │   └── stores/
│   └── tailwind.config.js
├── docs/
├── docker/
├── .github/workflows/
├── CLAUDE.md
└── PLAN.md
```

Fiecare `Domain/*` conține `Actions/`, `Models/`, `DTOs/`, `Jobs/`, `Policies/`. Fără God Services.

---

## 9. Spațiul din calea proiectului — risc condiționat

Proiectul stă la `/Volumes/T7 1/Wishio`. Spațiul din „T7 1" **nu afectează** fluxul normal de lucru:

| Ce faci | Unde rulează build-ul | Afectat de spațiu |
|---|---|---|
| `npx expo start` + Expo Go | Metro local, doar JS | ❌ nu — verificat, bundle-ul web s-a construit din această cale |
| `eas build` (cloud) | pe serverele Expo, Linux | ❌ nu — calea ta e irelevantă |
| `npx expo prebuild` / `run:android` / `run:ios` | Gradle / CocoaPods, local | ⚠️ **da, aici e riscul** |
| `eas build --local` | Gradle / Xcode, local | ⚠️ da |

Gradle (Android) și CocoaPods (iOS) au un istoric de probleme cu spații în cale. Cât timp stai pe **managed workflow + EAS cloud builds**, nu atingi niciodată aceste unelte și nu ai de ce să muți proiectul.

**Când devine relevant:** dacă la un moment dat ai nevoie de `expo prebuild` (un modul nativ care cere configurare manuală) sau vrei build-uri locale ca să nu aștepți coada EAS.

**Dacă ajungi acolo, în ordinea preferinței:**
1. Rămâi pe EAS cloud builds — cel mai simplu, și oricum necesar pentru distribuție în store.
2. Mută proiectul într-o cale fără spații (ex. `~/Projects/wishio`).
3. Symlink-ul **nu ajută** — verificat: Metro rezolvă calea reală, cu tot cu spațiu.

## 10. Decizii tehnice rămase

Vezi `docs/08-decizii-deschise.md`. Blocante pentru arhitectură:
- provider AI + regiunea de procesare
- unde stă baza de date (UE recomandat)
- MySQL 8.4 în producție (recomandat) — vezi § 2
- rezolvarea problemei de cale de la §9
