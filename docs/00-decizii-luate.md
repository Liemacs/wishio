# 00 — Decizii luate

> Registrul deciziilor. Fiecare decizie: ce, când, de ce, și ce alternativă am respins.
> Se completează pe măsură ce mergem. Deciziile deschise sunt în `docs/08-decizii-deschise.md`.

---

## D-001 · Ideea aleasă — Birthday/Gift Assistant
**Data:** 2026-09-12 · **Stare:** confirmată

Din trei idei evaluate (Stack Generator, Secret Santa + wishlist, Birthday/Gift Assistant), continuăm cu a treia.

**De ce:** utilizare recurentă (nu sezonieră ca Secret Santa), monetizare naturală prin comerț local, și un avantaj defensibil care nu e AI-ul, ci Gift Graph-ul acumulat.
**Respins:** Stack Generator (concurență globală enormă, înlocuibil de asistenți AI), Secret Santa simplu (sezonier, concurenți maturi — Elfster are 47M+ utilizatori).

## D-002 · Trei limbi de la lansare — RO bază, RU, EN
**Data:** 2026-09-12 · **Stare:** confirmată

RO este limba de bază și sursa de adevăr pentru traduceri. RU și EN au paritate funcțională, cu fallback la RO.

**De ce:** în Moldova, absența RU înseamnă pierderea a ~jumătate din piață. EN deblochează diaspora și extinderea.
**Consecință:** i18n este cerință de arhitectură, nu feature. Definition of Done include traducerile. Vezi `docs/05 § 6`.

## D-003 · Stack
**Data:** 2026-09-12 · **Stare:** confirmată

React Native + Expo + **gluestack-ui** (NativeWind) · **Laravel 12** · **MySQL 8.4** · Redis/Horizon · Inertia+Vue pentru web public.

**De ce:** competența existentă în Laravel = viteză maximă. gluestack-ui e copy-paste (control total) și universal mobile+web.
**Consecință MySQL:** fără `pgvector`. Compensat prin scoring determinist la MVP (suficient pentru un catalog de 300–500 produse) și Meilisearch la v1.1. Avantaj: `utf8mb4_0900_ai_ci` rezolvă nativ diacriticele românești. Vezi `docs/05 § 2`.

## D-004 · Modelul de identitate — fără shadow profiles
**Data:** 2026-09-12 · **Stare:** confirmată

Modelul „shared Person data îmbogățit automat din agendele mai multor utilizatori” din discuția inițială **se abandonează**. Agenda nu se urcă pe server; doar HMAC-uri ale numerelor selectate explicit. Datele unei persoane devin partajabile **numai după consimțământul ei**.

**De ce:** Legea 195/2024 (în vigoare 23 aug 2026, transpune GDPR) + App Store Guideline 5.1.2. Modelul original ar fi blocat publicarea și ar fi expus la amenzi de până la 2 mil. MDL.
**Ce păstrăm:** ideea de `source` + `confidence` + override-uri per utilizator, care era corectă și rămâne integral. Vezi `docs/04 § 2–3`.

## D-005 · Occasion Engine, nu Birthday Engine
**Data:** 2026-09-12 · **Stare:** confirmată

Produsul urmărește zile de naștere, **onomastici**, sărbători cu cadouri și aniversări.

**De ce:** onomastica se derivă automat din prenume, fără nicio dată introdusă de utilizator — este cel mai bun activ de cold-start al produsului. Și triplează numărul de ocazii pe persoană, eliminând sezonalitatea.

---

## De completat (P0.1)

### A1 · Ore disponibile pe săptămână
> _de completat_

### A2 · Singur sau cu echipă (cine face design?)
> _de completat_

### A3 · Buget pentru primele 6 luni
> _de completat_

### A4 · Criteriul de abandon
> _de completat — scrie-l acum, nu peste 3 luni_

### A5 · Numele final + domeniu
> _de completat după P0.4_

---

## D-006 · Layer de stilizare — DESCHISĂ ⚠️
**Data:** 2026-09-12 · **Stare:** blochează S1.6

**Constatare din testare reală** (nu din documentație):

| Combinație | Rezultat |
|---|---|
| Expo SDK 57 + NativeWind **v4.2.6** (stabil) + Tailwind 3 | ❌ nu compilează — Metro din SDK 57 e incompatibil |
| Expo SDK 57 + NativeWind **v5.0.0-preview.4** + Tailwind 4 | ✅ **compilează**, token-urile Wishio ajung în CSS |
| gluestack-ui **v2** (linia documentată, stabilă) | cere NativeWind v4 + Tailwind 3 → cere Expo SDK ≤54 |
| gluestack-ui **v5** CLI | `Welcome to gluestack-ui v5 alpha` — alpha, `init` interactiv |
| `@legendapp/motion` (dependință gluestack v2) | blochează NativeWind v5 (peer `>=4.0.0` nu acceptă prerelease) |

**Starea actuală a repo-ului:** Expo SDK 57 + NativeWind v5 preview + Tailwind v4, build verificat, fără gluestack.

**Opțiunile:**

**A — Rămânem pe SDK 57 + NativeWind v5 preview.** Fără gluestack deocamdată; componentele de bază (Button, Card, Input, Sheet) se scriu manual pe NativeWind — sunt oricum copy-paste și în gluestack. gluestack v5 se adaugă când iese din alpha.
*Plus:* SDK modern, build dovedit, zero dependențe alpha în runtime.
*Minus:* NativeWind v5 e preview; scrii ~8 componente de bază singur (1–2 zile).

**B — Coborâm la Expo SDK 54 + NativeWind 4.2.6 + Tailwind 3 + gluestack-ui v2.** Combinația documentată și folosită de comunitate.
*Plus:* gluestack funcționează azi, set complet de componente.
*Minus:* SDK mai vechi, reinstalare completă, iar issue-urile raportate pe SDK 54 + gluestack (Reanimated, overlay) trebuie verificate.

**De decis înainte de S1.6.** Vezi `docs/05-arhitectura.md § 1`.
