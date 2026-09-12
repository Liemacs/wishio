# 05 — Arhitectură tehnică

> Stack confirmat de fondator: **React Native + gluestack-ui**, **Laravel**, **MySQL**.
> Documentul de mai jos justifică alegerile, marchează unde ele au consecințe reale și ce compensăm.

---

## 1. Stack

| Strat | Alegere | Note |
|---|---|---|
| Mobile | **React Native + Expo (SDK 54+) + TypeScript** | Contacts, push, deep links, widget-uri, un codebase iOS+Android. EAS Build elimină pipeline-ul nativ. |
| UI | **gluestack-ui v2 + NativeWind v4** | Componente copy-paste (model shadcn) → codul e în repo, control total. Universal: aceleași componente merg și pe web. |
| Navigație | **expo-router** | rutare pe fișiere, deep links gratuit (esențial pentru `@slug` → app) |
| State | **Zustand** (UI) + **TanStack Query** (server state) | cache, retry, offline, invalidare |
| i18n mobile | **i18next + expo-localization** | RO sursă, fallback RO, plural RU corect |
| Backend | **Laravel 12 / PHP 8.3** | competența ta = viteză maximă. Nu experimenta aici. |
| DB | **MySQL 8.4 LTS** | vezi §2 pentru consecințe și compensări |
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

### De ce gluestack-ui este o alegere bună aici
- **Copy-paste, nu dependență opacă** — componentele ajung în `mobile/components/ui/`, le modifici fără fork.
- **NativeWind** = Tailwind în React Native: un singur limbaj de stilizare pentru mobile și pentru web-ul Inertia.
- **Universal** — aceleași componente pot alimenta mai târziu o versiune web a aplicației.
- **Accesibilitate** implicită (bazat pe primitive accesibile), ceea ce contează la App Review.

**Capcane de știut din prima zi:**
- gluestack-ui v2 cere NativeWind v4 și configurare Babel/Metro corectă — se face o dată, la scaffold, nu mai târziu.
- Temele se definesc prin token-uri în `tailwind.config.js` + config gluestack. **Definește paleta și tipografia înainte de al doilea ecran**, altfel rescrii tot.
- Textele **RU sunt cu 10–15% mai lungi decât RO** — toate componentele se testează în RU, nu în EN.

---

## 2. MySQL — consecințe și compensări

MySQL e alegerea ta și este perfect viabilă. Dar are trei consecințe reale față de PostgreSQL, pe care le compensăm explicit:

| Consecință | Compensare |
|---|---|
| **Fără `pgvector`.** MySQL 8.4 nu are tip vector nativ (MySQL 9.x are, dar ecosistemul Laravel e subțire). | Ranking-ul MVP nu are nevoie de embeddings — vezi §3. Căutarea semantică vine la v1.1 prin **Meilisearch**, care e oricum mai bun pentru RO/RU/EN. |
| **Full-text mai slab**, mai ales pentru limba rusă (fără stemming). | Idem — Meilisearch la v1.1. Pentru MVP, căutarea e pe categorii și filtre, nu pe text liber. |
| **JSON mai puțin puternic decât JSONB** (fără indexare GIN directă). | Traducerile se țin în coloane `JSON`, iar pentru cele filtrate des se adaugă **coloane generate + index**: `title_ro VARCHAR(255) AS (translations->>'$.ro') STORED, INDEX(title_ro)`. |

**Un avantaj real al MySQL pentru acest proiect:** collation `utf8mb4_0900_ai_ci` este *accent-insensitive și case-insensitive nativ*. Asta rezolvă gratuit potrivirea numelor românești cu diacritice (`Ștefan` = `stefan`) — exact ce cere name-day resolver-ul.

### Convenții MySQL obligatorii
```sql
CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci   -- implicit pe toată baza
-- excepție: coloanele de hash și slug → utf8mb4_bin (comparație exactă)
```
- `contact_hash` → `CHAR(64)` + index unic compus cu `user_id`
- sume de bani → `DECIMAL(12,2)`, **niciodată** `FLOAT`
- toate datele stocate UTC; conversia la fusul userului se face în aplicație
- `innodb_default_row_format=DYNAMIC`; atenție la limita de 3072 bytes pe index cu utf8mb4 → prefixe pe coloane text lungi
- migrări: `$table->json('translations')` + coloane generate unde ai nevoie de index

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
├── mobile/                   Expo + RN + gluestack-ui
│   ├── app/                  expo-router
│   ├── src/
│   │   ├── components/ui/    componente gluestack (copy-paste)
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

## 9. ⚠️ Problemă de mediu — spațiul din calea proiectului

Proiectul este la `/Volumes/T7 1/Wishio`. **Spațiul din „T7 1” rupe build-urile native React Native** — Gradle (Android) și CocoaPods (iOS) au probleme documentate cu spații în cale. Expo Go va merge; `expo run:ios`, `expo run:android` și build-urile locale EAS pot eșua cu erori greu de diagnosticat.

**Soluții, în ordinea preferinței:**
1. Mută proiectul într-o cale fără spații (ex. `~/Projects/wishio`).
2. Creează un symlink și lucrează prin el:
   ```bash
   ln -s "/Volumes/T7 1/Wishio" ~/wishio
   ```
   apoi deschide tot prin `~/wishio`.
3. Lasă `mobile/` pe discul intern și `backend/` pe extern (rupe monorepo-ul — nerecomandat).

Fă asta **înainte** de primul build nativ, nu după.

---

## 10. Decizii tehnice rămase

Vezi `docs/08-decizii-deschise.md`. Blocante pentru arhitectură:
- provider AI + regiunea de procesare
- unde stă baza de date (UE recomandat)
- MySQL 8.4 LTS (recomandat) vs. 9.x
- rezolvarea problemei de cale de la §9
