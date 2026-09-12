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

## D-006 · Layer de stilizare — NativeWind v5, fără gluestack
**Data:** 2026-09-12 · **Stare:** confirmată

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

**Decizia: A.** Rămânem pe Expo SDK 57 + NativeWind v5 preview + Tailwind v4.

**De ce:** build-ul e dovedit pe stack-ul real; nu introducem dependențe alpha în runtime; SDK-ul modern
ne scutește de o migrare peste 6 luni. Componentele de bază (Button, Card, Input, Sheet, Avatar, Badge,
ListItem, EmptyState) se scriu manual pe NativeWind — sunt copy-paste și în gluestack, deci nu pierdem
nimic conceptual, doar ~1–2 zile de muncă.

**Consecință:** `mobile/src/components/ui/` devine design system-ul propriu, construit pe token-urile din
`global.css`. gluestack-ui se poate adopta mai târziu, când v5 iese din alpha — componentele noastre au
aceeași formă (copy-paste peste NativeWind), deci migrarea ar fi incrementală, nu o rescriere.

**Revizuim această decizie dacă:** NativeWind v5 rămâne în preview peste ~6 luni, sau gluestack v5 devine
stabil înainte de S9. Vezi `docs/05-arhitectura.md § 1`.

---

## D-007 · Baza de date — MariaDB în dev, MySQL 8.4 în producție
**Data:** 2026-09-12 · **Stare:** confirmată

Dezvoltarea rulează pe **MariaDB 10.4.28** (XAMPP, deja instalat). Producția și CI rulează **MySQL 8.4 LTS**.

**Colația: `utf8mb4_unicode_ci`**, aleasă după test empiric pe serverul real:

| Colație | `'Ștefan' = 'stefan'` |
|---|---|
| `utf8mb4_unicode_ci` | ✅ |
| `utf8mb4_general_ci` | ✅ (legacy) |
| `utf8mb4_romanian_ci` | ❌ — tratează diacriticele ca litere distincte |
| `utf8mb4_0900_ai_ci` | nu există în MariaDB |

Potrivirea insensibilă la diacritice este condiția de funcționare a name-day resolver-ului, deci colația nu e un detaliu. `utf8mb4_romanian_ci` ar fi fost alegerea „evidentă" și greșită.

**Avertisment:** MariaDB 10.4 este **EOL din iunie 2024**. Acceptabil în dev, inacceptabil în producție pentru un produs care prelucrează date personale sub Legea 195/2024. `docker/compose.yaml` pornește MySQL 8.4 pentru CI și staging.

**Verificat:** migrările Laravel 12 rulează pe MariaDB 10.4 fără modificări; `/api/v1/ping` răspunde localizat în RO/RU/EN cu fallback corect la RO.

---

## D-008 · Catalogul — API Magaziner confirmat
**Data:** 2026-09-12 · **Stare:** confirmată verbal, **de formalizat**

Proprietarul Magaziner este de acord să ofere un API cu toate produsele. Asta închide riscul R4 din `docs/02`, care era al patrulea ca gravitate.

**Ce se schimbă:**

| Înainte | Acum |
|---|---|
| `ManualCatalog` cu 300–500 produse construite de mână (3–4 zile) | `MagazinerCatalog` cu ~70.000 de produse din 15+ magazine |
| Riscul: catalog prea mic → „0 rezultate" | Riscul se **inversează**: catalog prea mare și needitat → recomandări proaste (R7) |
| Muncă: *construirea* catalogului | Muncă: **curatarea** catalogului |

**Consecința importantă:** 70.000 de produse needitate dau recomandări *mai proaste* decât 400 curatate. Un router Wi-Fi și o pereche de căști au același preț și aceeași categorie, dar unul nu e cadou. Vezi `docs/05 § 4` pentru noul pipeline: **ingestie totală + strat de curatare deasupra**.

**De formalizat, chiar dacă relația e bună:** un document de o pagină cu ce oferă fiecare parte, cine deține datele, ce se întâmplă cu tracking-ul de clickuri și cum se împart veniturile. Nu din neîncredere — ci pentru că peste doi ani nimeni nu-și mai amintește ce s-a înțeles la telefon. Vezi `docs/12`.

**Oportunitate secundară:** proprietarul cunoaște piața de e-commerce din Moldova mai bine decât oricine. Merită întrebat direct despre B3 și B5 (plătesc comercianții? există afiliere reală?) și cerută o introducere la 2–3 magazine.

---

## D-009 · Nume și domeniu — Wishio / wishio.md
**Data:** 2026-09-12 · **Stare:** confirmată

Numele produsului este **Wishio**. Domeniul principal: **wishio.md**.

**Verificat la 2026-09-12:**

| Domeniu | Stare |
|---|---|
| `wishio.md` | liber → **ales** |
| `wishio.ro` | liber → **de înregistrat acum**, e piața #2 (`docs/02 § 1`) |
| `wishio.io` | liber |
| `wishio.com` | ocupat, parcat la un revânzător (NS `ztomy.com`) — cumpărabil, probabil scump |
| `wishio.app` | ocupat și **folosit activ** (A record pe Vercel) |

**Consecințe:**
- Linkurile publice sunt `wishio.md/@slug`. Scurt, local, potrivit.
- `wishio.ro` costă ~10 €/an. Răscumpărarea lui peste doi ani, după ce produsul are tracțiune, costă de 50–100 de ori mai mult. Înregistrează-l odată cu `.md`.
- `wishio.app` fiind folosit activ de altcineva: **verifică numele „Wishio" în App Store și Google Play** înainte de submit, și verifică marca la AGEPI (MD) și EUIPO (dacă mergi în RO). Riscul e mic, dar se verifică în 20 de minute.
- `.com` nu e necesar acum. Dacă vreodată produsul crește, se negociază atunci.
