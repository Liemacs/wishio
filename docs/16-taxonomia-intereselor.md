# 16 — Taxonomia de interese

> Contractul dintre AI și catalog. Fără ea, LLM-ul returnează text liber („îi plac chestiile tehnice") care nu se mapează pe niciun produs.
> Vezi `docs/04 § 5` și `docs/05 § 3`.

---

## 1. Ce s-a construit

| Componentă | Unde |
|---|---|
| Tabele `interest_groups` + `interests` | `backend/database/migrations/2026_09_12_130000_*` |
| Date — 15 grupuri, 80 de interese | `backend/database/seeders/data/interests.php` |
| Seeder | `backend/database/seeders/InterestSeeder.php` |
| Potrivire din text liber | `backend/app/Domain/People/Actions/MatchInterestsFromText.php` |
| Teste | `backend/tests/Feature/InterestTaxonomyTest.php` |

**În bază:** 15 grupuri, **80 de interese** (9 experiențe), **1.164 de cuvinte-cheie** în RO/RU/EN.

---

## 2. Structura

Două niveluri, nu trei. Un selector cu trei niveluri de imbricare e obositor pe telefon.

```
grup (15)                    interes / frunză (80)
─────────────────────────────────────────────────────────
💻 Tehnologie                audio · computing · mobile_accessories ·
                             photo_video · smart_home · wearables · drones
🚗 Auto                      car_care · car_accessories · motorsport
🏋️ Sport                     fitness_gym · running · cycling · outdoor_hiking ·
                             team_sports · winter_sports · water_sports
🎮 Gaming                    console_gaming · pc_gaming · board_games
💄 Frumusețe                 fragrance_women · fragrance_men · skincare ·
                             makeup · hair_care · grooming_men
👜 Modă                      watches · jewelry · bags · footwear · apparel ·
                             accessories_fashion
🏠 Casă                      kitchen_gadgets · cookware · coffee_gear ·
                             home_decor · home_textiles · plants · tools_diy · bbq_grill
📚 Cultură                   books_fiction · books_nonfiction · music_instruments ·
                             vinyl_music · art_supplies · cinema_home
☕ Mâncare și băutură        coffee · tea · wine · spirits · sweets · gourmet_food
🧘 Wellness                  spa_products · massage · yoga · sauna
✈️ Călătorii                 travel_gear · luggage · camping
🧸 Copii                     toys · construction_toys · educational_toys · baby_care
🐕 Animale                   dogs · cats
🎣 Hobby-uri                 fishing · hunting · gardening · handmade ·
                             collecting · astronomy
🎉 Experiențe                dining_out · karting_exp · escape_room ·
                             bowling_billiards · concerts · active_outdoor ·
                             wine_tasting · workshops · photo_session
```

### Câmpuri per interes

| Câmp | Rol |
|---|---|
| `code` | identificatorul din contractul AI — **singurele valori acceptate** |
| `translations` | RO / RU / EN, obligatorii toate trei |
| `keywords` | RO / RU / EN — vezi § 3 |
| `gender_affinity` | indiciu **slab** pentru ranking, niciodată filtru |
| `typical_min_price` / `max` | banda tipică în MDL — ajută potrivirea la buget când catalogul încă n-are produse mapate |
| `is_experience` | rutează spre Experience Engine (v1.1), nu spre catalogul de produse |

> `gender_affinity` este un indiciu, nu o regulă. Femeile cumpără unelte, bărbații cumpără parfum. Folosit ca filtru, produsul devine stupid și ușor ofensator.

---

## 3. Cuvintele-cheie fac două lucruri

1. **Text liber → interese.** Ecranul P5 („Spune-mi despre Alex"), și întreaga Fază 0, unde nu există AI.
2. **Titlu de produs → interese.** Maparea catalogului din `docs/05 § 4`, până când avem categoriile Magaziner.

Căutarea se face în cuvintele-cheie din **toate cele trei limbi simultan**. În Moldova, o propoziție de forma *„îi place футбол și mașinile"* e complet normală.

### Regula de potrivire — calibrată pe fals-pozitive reale

Potrivirea pe rădăcină (prefix comun) se aplică **doar cuvintelor de cel puțin 5 litere**. Sub prag cerem potrivire exactă.

Pragul a fost ridicat de la 4 la 5 după ce testarea pe descrieri realiste a produs trei erori:

| Text | Prindea greșit | De ce |
|---|---|---|
| „**Alex** lucrează ca programator" | `smart_home` | `alex` ≈ `alexa` pe 4 litere |
| „nimic **special**" | `concerts` | `spec`ial ≈ `spec`tacol |
| „ему нравится **футбол**" | `apparel` | `футбол` ≈ `футболка` — fals prieten real în rusă |

Ridicarea pragului le-a eliminat pe toate trei, fără a pierde formele flexionate: `mașinile` → `mașină`, `citește` → `citit`, `gătească` → `gătit` funcționează în continuare.

**Limitarea care rămâne, asumată:** `mașinile` se potrivește și cu `mașinuță` (jucării), la încredere 0.60. Este o ambiguitate reală a limbii; filtrul de vârstă din ranking o rezolvă.

### Încrederea nu atinge niciodată 1.0

| Cuvinte-cheie potrivite | Încredere |
|---|---|
| 3+ | 0.85 |
| 2 | 0.75 |
| 1 | 0.60 |

Interesul dedus stă **sub** cel confirmat de utilizator sau declarat de persoana însăși. Vezi ierarhia din `docs/04 § 3`.

---

## 4. Cum se folosește

### Ca listă pentru selector (ecranul P4)
```php
InterestGroup::with('interests')->orderBy('sort_order')->get();
$group->label('ru');       // "Технологии"
$interest->label('ro');    // "Audio și căști"
```

### Ca deducere din text liber (ecranul P5, și Faza 0)
```php
$matches = app(MatchInterestsFromText::class)('Îi plac mașinile și merge la sală');
// car_accessories 0.75 · fitness_gym 0.60
```

### Ca validare a rezultatului AI — obligatoriu
```php
$valid = array_intersect($aiResponse['interests'], Interest::validCodes());
```
Orice cod din afara taxonomiei **se aruncă, nu se repară**. Regula 2 din `CLAUDE.md`.

---

## 5. Ce rămâne de făcut

| Ce | Când | Blocat de |
|---|---|---|
| **Maparea interes → categorie de catalog** | la primirea setului de test Magaziner | `docs/12 § 2.1` — cere `GET /api/categories` |
| Calibrarea benzilor de preț pe prețuri reale | după ingestia catalogului | valorile actuale sunt estimări |
| Extinderea cuvintelor-cheie din conversațiile Faza 0 | după P0.8 | **cel mai bun material**: cum vorbesc oamenii real despre alți oameni |
| Prunarea intereselor fără produse în catalog | S6 | un interes fără produse produce „0 rezultate" |

> Ultimul punct contează mai mult decât pare. **Un interes pe care catalogul nu-l acoperă este mai rău decât un interes lipsă** — promiți ceva și nu livrezi. După ingestia catalogului, orice interes cu mai puțin de ~10 produse recomandabile fie primește produse, fie se scoate din selector.

### Reglaj fin după Faza 0
Cele 20 de conversații din `docs/14` sunt sursa cea mai bună de cuvinte-cheie. Oamenii nu scriu „interesat de audio" — scriu „ascultă muzică tot timpul", „e melomanul familiei". Adaugă exact acele formulări.
