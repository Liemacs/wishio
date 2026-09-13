# 04 — Modelul de domeniu

> Acest document înlocuiește modelul „shared Person + UserPerson” din conversația inițială.
> Ideea de bază (date comune + override-uri per utilizator) rămâne. **Sursa datelor comune se schimbă**, din motivele din `docs/02-strategie-riscuri.md § R2`.

---

## 1. Entitatea centrală: User ≠ Person

- **User** — cine folosește aplicația. Are cont.
- **Person** — cineva din viața unui user. **Nu are cont.** Există doar în contextul acelui user.
- Un Person poate *deveni* User mai târziu. Atunci se face „claim”, și profilul lui public devine sursa de adevăr.

---

## 2. Modelul de identitate, sigur din punct de vedere legal

> **Actualizare — `docs/00 § D-017`:** în MVP **nu colectăm deloc numere de telefon**. Schema de mai jos rămâne valabilă pentru momentul în care implementăm claim-ul, singura funcție care are nevoie de ea. Până atunci, importul trimite serverului doar numele și ziua de naștere, iar re-sincronizarea se face pe identificatorul local al contactului. Poza se citește din agendă doar ca să fie afișată, pe telefon (`docs/00 § D-023`).


### Ce NU facem
- Nu urcăm agenda brută pe server.
- Nu creăm profiluri globale îmbogățite din agendele altor utilizatori.
- Nu copiem niciodată ziua de naștere sau poza unei persoane de la User B la User A.

### Ce facem

```
DEVICE                                    SERVER
──────                                    ──────
Agenda telefonului                        (nimic)
  ↓ user selectează explicit persoanele
Pentru fiecare persoană selectată:
  normalize(phone) → E.164               
  HMAC-SHA256(phone_e164, PEPPER)  ────►  contact_hash  (opac, ireversibil)
  name, birthday, photo  ───────────────► rămân LOCALE (photo) /
                                          urcate doar ca date ale user-ului (name, birthday)
```

`contact_hash` nu permite recuperarea numărului. Serverul nu află niciodată agenda.

### La ce folosește `contact_hash` — un singur lucru

**Claim & consimțământ.** Când o persoană se înregistrează cu numărul `+37369123456`, serverul calculează același hash și poate:
1. să lege profilul ei public de intrările `Person` existente care au acel hash;
2. să o întrebe pe **ea**: *„3 persoane te au în agendă. Vrei ca ziua ta de naștere și interesele tale să le fie vizibile?”*;
3. abia după **DA-ul ei** se propagă datele.

**Nu se folosește niciodată pentru:** a arăta unui user cine altcineva are persoana în agendă, a număra, a sugera „persoane pe care le-ai putea cunoaște”, sau orice formă de descoperire socială.

> Fluxul din conversație („User B are poza, User A nu, o completăm automat”) devine:
> **„Ana însăși a pus o poză și a acceptat s-o vadă cine o are în agendă.”**
> Mai puține date, dar corecte, consimțite și de calitate mai bună.

---

## 3. Ierarhia de încredere (trust)

Fiecare câmp de pe un Person are `source` și `confidence`. La conflict, câștigă sursa superioară.

| Nivel | `source` | Exemplu | Prioritate |
|---|---|---|---|
| 1 | `subject_confirmed` | Ana a confirmat în app că ziua ei e 12 oct | **maximă** |
| 2 | `subject_provided` | Ana a completat linkul lui Maxim | foarte mare |
| 3 | `owner_manual` | Maxim a scris el ziua ei | mare |
| 4 | `device_contact` | citit din agenda lui Maxim | medie |
| 5 | `derived` | onomastica dedusă din prenume | mică |
| 6 | `ai_inferred` | AI a dedus „îi place fitness” din note | **cea mai mică**, marcată vizual |

**Regula de override:** dacă un user modifică manual un câmp, se setează `overridden_at`, iar câmpul nu mai este suprascris **niciodată** de o sincronizare ulterioară — nici de contacts sync, nici de claim. Acesta este principiul corect din conversația inițială și se păstrează integral.

---

## 4. Name-day resolver (onomastica) — activ strategic

Cel mai bun răspuns la cold-start (R1). Tabel static, construit o singură dată, întreținut manual.

```
name_days
├── id
├── country_code        -- MD, RO (diferă de RU/UA)
├── calendar            -- orthodox_new | orthodox_old | catholic
├── saint_name          -- "Sfântul Gheorghe"
├── month, day
└── translations        -- ro / ru / en

name_day_aliases
├── name_day_id
├── given_name_normalized  -- "gheorghe", "george", "gicu", "жора", "георгий"
├── gender
└── confidence             -- 0.95 pentru potrivire exactă, 0.6 pentru diminutiv
```

**Fluxul:** `prenume din contact → normalizare (diacritice, minuscule, transliterare RU→RO) → alias match → ocazie de tip name_day`, cu `source = derived` și confidence corespunzător.

**UX obligatoriu:** onomasticile derivate se prezintă separat și cu buton de confirmare:
> *„Credem că Gheorghe își serbează onomastica pe 23 aprilie. Corect?”* → ✓ / ✗ / „nu sărbătorește”

Nu trimitem push pentru onomastici neconfirmate cu confidence sub prag. Un reminder greșit e mai rău decât niciunul.

**Efort:** ~2–3 zile pentru primele 150 de prenume, care acoperă majoritatea populației. **Cel mai bun raport efort/impact din tot MVP-ul.**

---

## 5. Taxonomia de interese — trebuie definită înainte de AI

Fără o taxonomie fixă, AI-ul scoate string-uri libere („îi plac chestiile tehnice”) care nu se pot mapa pe categorii de produse. Atunci recomandările nu funcționează. **Aceasta este piesa lipsă cea mai importantă din planul inițial.**

Structură pe 3 niveluri:

```
domain           category              interest (leaf)
─────────────────────────────────────────────────────────
tech             audio                 casti, boxe, vinyl
                 computing             periferice, monitoare
                 mobile                accesorii telefon
                 smart_home            
auto             car_care              detailing, accesorii auto
                 motorsport            karting, curse
sport            fitness               sala, suplimente, echipament
                 outdoor               drumetii, camping, ciclism
                 team_sports           
gaming           console               
                 pc_gaming             periferice, scaune
                 board_games           
beauty           fragrance             parfum dama / barbati
                 skincare              
                 makeup                
fashion          accessories           ceasuri, bijuterii, genti
                 footwear              
                 apparel               
home             kitchen               cafea, gatit, vesela
                 decor                 
                 comfort               
culture          books                 fictiune, business, arta
                 music                 
                 cinema                
food_drink       coffee_tea            
                 wine_spirits          
                 gourmet               
wellness         spa                   
                 mindfulness           
travel           trips                 
                 gear                  
kids             toys, lego, educational
pets             dog, cat
```

**Reguli:**
- fiecare `interest` are `translations` (ro/ru/en) și un mapping către categoriile din catalog
- AI-ul primește taxonomia în prompt și **poate returna doar leaf-uri din ea** — validat server-side, orice altceva se aruncă
- fiecare legătură persoană↔interes are `source` + `confidence`, ca mai sus
- prima versiune: ~60–80 de leaf-uri. Nu mai mult — un catalog mic nu poate susține mai multe.

**Semnale negative sunt la fel de valoroase:** `avoid` — „fără haine”, „nu bea alcool”, „e vegetarian”. Un singur cadou nepotrivit strică mai mult decât ajută zece potrivite.

---

## 6. Vizibilitate (pe profilul propriu al userului)

Fiecare câmp de pe *propriul* profil are un nivel:

| Nivel | Sens |
|---|---|
| `private` | doar eu |
| `signal_only` | poate fi folosit pentru sugestii către alții, **fără a expune conținutul concret** |
| `contacts` | vizibil celor care mă au în agendă și au confirmat legătura |
| `public` | vizibil pe pagina mea publică |

Default-uri (conform discuției din conversație, păstrate):

| Câmp | Default |
|---|---|
| Ziua de naștere | `contacts` |
| Poză | `contacts` |
| Interese | `contacts` |
| **Item concret din wishlist** | `private` |
| **Categorie/semnal din wishlist** | `signal_only` |
| Locuri dorite | `private` |
| Experiențe dorite | `signal_only` |
| Note | `private` (întotdeauna) |
| Istoric cadouri | `private` (întotdeauna) |

`signal_only` este mecanismul elegant din conversație și merită păstrat exact: dacă Daniela are „AirPods” în wishlist, prietenul vede *„s-ar putea să-i placă audio/tech”*, nu itemul. Wishlistul rămâne privat, semnalul circulă.

---

## 7. Tabele

```
-- Identitate & cont
users                     id, email, phone_e164, phone_hash, locale(ro|ru|en),
                          country_code, timezone, birthday, birthday_visibility,
                          display_name, avatar_path, created_at
user_settings             user_id, reminder_days[], quiet_hours, push_enabled,
                          email_digest, preferred_currency
oauth_identities          user_id, provider(apple|google), provider_uid

-- Persoane (scoped pe user)
people                    id, user_id, display_name, given_name_normalized,
                          contact_hash, device_contact_id, relationship,
                          gender, birth_date, birth_year_known,
                          budget_min, budget_max, notes, avatar_path,
                          claimed_user_id (nullable), archived_at
person_field_sources      person_id, field, source, confidence, overridden_at
person_interests          person_id, interest_id, weight, source, confidence
person_avoids             person_id, interest_id | free_text

-- Ocazii
occasion_types            code(birthday|name_day|anniversary|holiday|custom),
                          translations, default_reminder_days[]
occasions                 id, user_id, person_id (nullable pt sărbători globale),
                          type, month, day, year (nullable), recurrence,
                          confirmed_at, source, confidence, is_muted
holidays                  country_code, code(8_march|christmas|easter|...),
                          date_rule, translations, gift_relevant(bool)
name_days                 country_code, calendar, saint_name, month, day, translations
name_day_aliases          name_day_id, given_name_normalized, gender, confidence

-- Catalog
merchants                 id, name, country_code, website, logo, commission_model,
                          status, translations
products                  id, merchant_id, external_id, title, description,
                          price, currency, image_url, deeplink, in_stock,
                          gift_score, last_seen_at, translations
product_categories        id, parent_id, code, translations
product_interests         product_id, interest_id, weight
experiences               id, name, city, type, price_from, price_to,
                          group_size_min/max, address, phone, booking_url,
                          translations
interests                 id, domain, category, code, translations

-- Recomandări & acțiuni
recommendation_runs       id, user_id, person_id, occasion_id, budget_min/max,
                          kind(gift|experience), locale, criteria_json,
                          model, tokens, cost, status, created_at
recommendation_items      run_id, product_id | experience_id, rank, score,
                          reason_ro, reason_ru, reason_en, shown_at
gift_ideas                user_id, person_id, product_id (nullable) | title,
                          price, currency, status (idea|chosen|purchased);
                          „am oferit” mută ideea în gift_history
gift_history              user_id, person_id, product_id (nullable), title,
                          year, occasion_type, amount
outbound_clicks           user_id, product_id, merchant_id, run_id,
                          clicked_at, context

-- Profil propriu & link public
wishlist_items            user_id, kind(product|place|experience),
                          title, product_id, url, priority(want|maybe),
                          visibility, note
public_profiles           user_id, slug, locale_default, is_active, view_count
profile_submissions       target_user_id, submitted_name, birth_date,
                          interests_json, message, ip_hash, created_at,
                          consent_text_version, accepted_at

-- Notificări
notifications             user_id, occasion_id, channel(push|email),
                          scheduled_for, sent_at, opened_at, locale, template
device_tokens             user_id, platform, token, locale, last_active_at

-- i18n
translations              translatable_type, translatable_id, locale, field, value
```

### Notă despre i18n în bază

Conținutul editorial (categorii, interese, sărbători, onomastici, experiențe, texte de notificări) este **tradus**, nu duplicat. Două opțiuni, alege una și fii consecvent:

- **A — coloane JSONB:** `translations jsonb` pe fiecare tabel, forma `{"ro": "...", "ru": "...", "en": "..."}`. Simplu, rapid, un singur query. **Recomandat pentru MVP.**
- **B — tabel `translations` polimorfic.** Mai curat conceptual, mai multe join-uri.

**Regula:** RO este întotdeauna obligatoriu (limba de bază). RU și EN au fallback la RO dacă lipsesc. Niciun ecran nu afișează vreodată o cheie de traducere brută.

Datele generate de AI (motivele recomandărilor) se generează **în limba userului la momentul rulării** și se stochează în `reason_ro/ru/en` — se completează doar limba cerută, restul se generează la cerere (lazy), ca să nu triplezi costul.

---

## 8. Diagrama relațiilor esențiale

```
                    ┌──────────┐
                    │   User   │──────┐
                    └────┬─────┘      │ propriul profil
                         │            ├── wishlist_items
              are multe  │            ├── public_profile (@slug)
                         ▼            └── occasions (ziua mea)
                    ┌──────────┐
                    │  Person  │  (scoped pe user, fără cont)
                    └────┬─────┘
                         │
        ┌────────────────┼────────────────┬──────────────┐
        ▼                ▼                ▼              ▼
   occasions      person_interests   gift_history   contact_hash
   (birthday,      (+ source,         (anti-         │
    name_day,       confidence)        repetare)     │ doar pentru claim
    anniversary)                                     │ cu consimțământ
        │                │                           ▼
        └────────┬───────┘                    ┌─────────────┐
                 ▼                            │ User (Ana)  │
        ┌─────────────────┐                   │ când se     │
        │ Recommendation  │                   │ înregistrează│
        │     Engine      │                   └─────────────┘
        └────────┬────────┘
                 │
         ┌───────┴────────┐
         ▼                ▼
    products         experiences
         │                │
         └───────┬────────┘
                 ▼
          outbound_clicks  ──►  monetizare
```
