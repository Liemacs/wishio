# Wishio

**Nu uiți nicio ocazie. Știi exact ce să faci.**

Asistent de ocazii și cadouri pentru Republica Moldova — zile de naștere, **onomastici**, sărbători, aniversări — cu sugestii de cadouri și experiențe din magazine reale, locale, în bugetul tău.

**RO** (limba de bază) · **RU** · **EN**

---

## Stare

**Faza 0 — validare.** Infrastructura e pregătită. Prima componentă de produs — **resolver-ul de onomastici** (`docs/15`) — este implementată și testată.
Pasul următor: `PLAN.md § FAZA 0`.

## Stack

| | |
|---|---|
| Mobile | React Native + Expo (SDK 57) + TypeScript + **NativeWind v5** (Tailwind v4) + expo-router |
| Backend | **Laravel 12** / PHP 8.3 |
| Bază de date | **MySQL 8.4** în producție, **MariaDB 10.4** (XAMPP) în dev · colație `utf8mb4_unicode_ci` — accent-insensitive, critică pentru diacritice RO |
| Cozi / cache | Redis + Laravel Horizon |
| Web public | Laravel + Inertia + Vue 3 (landing, pagini `@slug`, merchant dashboard) |
| Observabilitate | PostHog + Sentry |

## Structură

```
wishio/
├── backend/      Laravel 12 — API, web public, cozi
│   ├── app/Domain/        People Occasions Reminders Catalog
│   │                      Recommendations Identity Profiles Merchants
│   ├── app/Support/       Ai/ Localization/ PhoneNumbers/
│   ├── config/wishio.php  configurația produsului
│   └── lang/{ro,ru,en}/
├── mobile/       Expo + React Native + NativeWind
├── docker/       MySQL 8.4 + Redis + Mailpit
└── docs/         documentația de produs și arhitectură
```

## Pornire rapidă

```bash
make setup     # composer install, key:generate, migrate, npm install
make api       # API pe http://localhost:8000
make mobile    # Expo
```

Baza de date rulează deja prin XAMPP (MariaDB). Pentru MySQL 8.4 + Redis + Mailpit în containere:
`make up` (cere Docker sau OrbStack).

Verificare rapidă că i18n merge cap-coadă:

```bash
curl -s -H "Accept-Language: ru" http://localhost:8000/api/v1/ping
```

`make help` listează toate comenzile.

## Documentație

| Document | Conținut |
|---|---|
| [docs/00-decizii-luate.md](docs/00-decizii-luate.md) | Registrul deciziilor luate, cu motivele |
| [docs/01-produs.md](docs/01-produs.md) | Viziune, poziționare, public țintă, bucle de creștere, principii |
| [docs/02-strategie-riscuri.md](docs/02-strategie-riscuri.md) | Dimensiunea pieței, 10 riscuri cu mitigări, porți de abandon |
| [docs/03-scop.md](docs/03-scop.md) | Faza 0, MVP, v1.1, v2, v3 — și ce NU construim |
| [docs/04-model-domeniu.md](docs/04-model-domeniu.md) | Entități, identitate sigură legal, ierarhia de trust, onomastici, taxonomia de interese |
| [docs/05-arhitectura.md](docs/05-arhitectura.md) | Stack, module, recommendation engine anti-halucinație, MySQL, i18n |
| [docs/06-privacy-legal.md](docs/06-privacy-legal.md) | Legea 195/2024, App Store 5.1.2, harta datelor, fluxuri de consimțământ |
| [docs/07-metrici.md](docs/07-metrici.md) | North Star, funnel, schema de evenimente, praguri |
| [docs/08-decizii-deschise.md](docs/08-decizii-deschise.md) | **Ce lipsește ca să începi** |
| [docs/09-ecrane-fluxuri-copy.md](docs/09-ecrane-fluxuri-copy.md) | 31 de ecrane, fluxurile principale, strategia de copy și scara de notificări |
| [docs/10-operare.md](docs/10-operare.md) | Medii, CI/CD, release mobile, testare, backup, monitorizare, incidente |
| [docs/11-costuri-gtm.md](docs/11-costuri-gtm.md) | Costuri, economia unitară, monetizare, primii 1.000 de utilizatori |
| [docs/12-integrare-magaziner.md](docs/12-integrare-magaziner.md) | Ce să ceri și ce să agreezi cu Magaziner — gata de trimis |
| [docs/13-recomandari.md](docs/13-recomandari.md) | **Recomandarea mea pentru fiecare punct deschis** |
| [docs/14-faza-0.md](docs/14-faza-0.md) | **Faza 0 în detaliu** — trei variante și planul zi cu zi |
| [docs/15-onomastici.md](docs/15-onomastici.md) | Onomasticile: cum funcționează, și ⚠️ ce trebuie verificat înainte de lansare |
| [PLAN.md](PLAN.md) | **Planul complet, pas cu pas** |
| [CLAUDE.md](CLAUDE.md) | Reguli de dezvoltare |

## Cele 10 reguli care nu se negociază

Sunt în [CLAUDE.md](CLAUDE.md). Cele mai importante trei:

1. **Trei limbi, mereu.** Nicio funcționalitate nu e terminată fără RO + RU + EN.
2. **AI-ul nu inventează niciodată un produs.** Produsele vin exclusiv din catalogul nostru.
3. **Nicio dată despre o persoană nu ajunge la alt utilizator** fără consimțământul ei explicit.
