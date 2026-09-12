# CLAUDE.md — Reguli pentru lucrul în acest repository

## Contextul proiectului

**Wishio** — asistent de ocazii și cadouri. Utilizatorul nu uită nicio ocazie (zile de naștere, **onomastici**, sărbători, aniversări) și primește sugestii concrete de cadouri și experiențe din magazine reale, locale, în bugetul lui.

Piața de start: **Republica Moldova**. Piața următoare: România.
Reminder-ul este mecanismul de retenție; **decizia de cumpărare este produsul**.

Înainte de orice sarcină semnificativă, citește `docs/01-produs.md`, `docs/03-scop.md` și `docs/04-model-domeniu.md`.

---

## Reguli absolute (nu se negociază)

1. **Trei limbi, mereu.** RO este limba de bază și sursa de adevăr. RU și EN au paritate funcțională, cu fallback la RO. Nicio funcționalitate nu e terminată fără traduceri. Niciun string hardcodat în UI. Niciun ecran nu afișează o cheie brută.
2. **AI-ul nu inventează niciodată un produs.** LLM-ul produce criterii și explicații. Produsele vin exclusiv din catalogul nostru, validate server-side. Vezi `docs/05-arhitectura.md § 3`.
3. **Nicio dată despre o persoană nu devine vizibilă altui utilizator** fără consimțământul explicit al persoanei respective. Fără enrichment silențios între utilizatori. Vezi `docs/04 § 2` și `docs/06 § 2`.
4. **Agenda telefonului nu se urcă pe server.** Doar hash-uri HMAC ale numerelor normalizate, doar pentru contactele selectate explicit. Pozele de contact rămân pe device.
5. **Aplicația funcționează complet fără permisiune de Contacts și fără push.** Ambele rute se testează la fiecare release.
6. **Contextul trimis spre AI este pseudonimizat.** Fără nume, telefoane, emailuri, note libere.
7. **Fiecare câmp are `source` și `confidence`.** Ierarhia: `subject_confirmed > subject_provided > owner_manual > device_contact > derived > ai_inferred`. Un override manual nu se suprascrie niciodată prin sync.
8. **Nimic lent nu blochează request-ul.** AI, sync, notificări → cozi.
9. **Fără feature-uri de rețea socială.** Fără feed, followers, chat public, descoperire de persoane.
10. **Aplicația nu trimite niciodată mesaje în numele utilizatorului.** Linkul personal se partajează manual, de către el.

## Design

Fundamentele de mișcare și tipografie sunt în `docs/19-design.md`, iar codul în
`mobile/src/design/`. Pe scurt:

- **Arcuri, nu durate fixe**, pe orice poate fi atins. `spring('press')` la apăsare,
  `spring('default')` la repoziționări. Salt (`momentum`, `sheet`) doar după un gest
  care a purtat viteză.
- **Feedback la apăsare, nu la eliberare**, și continuu pe durata gestului.
- **`useReducedMotion()` pe orice animație de deplasare.** Mișcarea redusă înseamnă
  opacitate în loc de deplasare — nu absența feedbackului. Haptica rămâne.
- **Spațierea dintre litere depinde de mărime.** Folosește scara din `TYPE`, nu
  clase Tailwind de mărime pe titluri.
- **Haptică doar la momente care contează.** În exces, oamenii o ignoră.

## Convenții

- Backend: Laravel 12, PHP 8.3+, PostgreSQL 16, Redis/Horizon. Cod organizat pe `app/Domain/*` cu `Actions/`, nu God Services.
- Mobile: Expo + TypeScript + expo-router, Zustand (UI) + TanStack Query (server state), i18next.
- Traduceri: JSONB `translations` pe modelele de conținut; `lang/{ro,ru,en}` pentru backend; `i18n/*.json` pentru mobile.
- API: REST versionat `/api/v1`, contract OpenAPI 3.1 actualizat odată cu endpointul.
- Comentarii și denumiri: **engleză în cod**, română în documentație și în copy-ul de produs.
- Migrări: niciodată editate după merge; întotdeauna migrare nouă.
- Teste: orice regulă din lista de mai sus care poate fi testată, **trebuie** testată (mai ales 2, 3, 4, 7).

## Când propui ceva nou

Întreabă-te, în ordine:
1. Întărește **memoria**, **declanșatorul** sau **catalogul real**? (dacă nu — probabil nu-l facem, vezi `docs/02 § R9`)
2. Este în `docs/03-scop.md` pentru faza curentă? Dacă nu, spune-o explicit înainte să implementezi.
3. Încalcă vreuna din cele 10 reguli absolute?
4. Funcționează în RO, RU și EN?
