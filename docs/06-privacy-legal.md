# 06 — Privacy, conformitate și App Store

> Nu este un capitol de „mai târziu”. Este capitolul care decide dacă aplicația **poate fi publicată**.
> Produsul manipulează: agende telefonice, date despre minori posibil, date despre terți care nu sunt utilizatori, și trimite context personal către un AI extern. Toate trei sunt zone cu respingeri frecvente.

---

## 1. Cadrul legal aplicabil

### Republica Moldova — Legea nr. 195/2024
- **În vigoare din 23 august 2026.** Înlocuiește Legea 133/2011.
- Transpune integral **Regulamentul UE 2016/679 (GDPR)**.
- Introduce **răspundere strictă**: operatorul trebuie să *demonstreze* conformitatea, nu doar să o afirme.
- Sancțiuni: până la ~2 milioane MDL.
- Drepturi extinse pentru persoana vizată, inclusiv dreptul la ștergere.

**Ce înseamnă practic pentru noi:**
1. Temei legal documentat pentru fiecare prelucrare.
2. **Registru de evidență a prelucrărilor.**
3. **DPIA** (evaluare de impact) — aproape sigur necesară: prelucrăm date ale unor persoane care nu sunt utilizatori, la scară, cu profilare.
4. Politică de confidențialitate reală, în **RO, RU și EN**.
5. Mecanism funcțional de export și ștergere a datelor.
6. Contracte cu împuterniciții (provider AI, hosting, analytics, push).
7. Dacă serverele sunt în UE — transfer simplificat; dacă AI-ul procesează în SUA, se documentează transferul.

### Dacă atingem UE (diaspora, România)
GDPR direct aplicabil. Aceleași obligații, plus posibil reprezentant în UE. Design-ul de mai jos este deja GDPR-compatibil, deci nu costă suplimentar.

---

## 2. App Store — regulile care ne pot respinge

### Guideline 5.1.2 — informații despre terți
> Apps that compile personal information from any source that is not directly from the user or without the user's explicit consent are not permitted.

Aplicațiile care colectează informații despre prietenii/contactele userului **fără cunoștința și consimțământul acelor persoane** sunt respinse.

**Ce face designul nostru compatibil:**

| Cerință | Cum o respectăm |
|---|---|
| Nu compila date despre terți | Agenda nu se urcă. Serverul primește doar `contact_hash` + datele pe care **userul** le păstrează pentru uz propriu |
| Nu partaja date între utilizatori | **Interzis prin arhitectură.** Datele unei persoane devin partajabile doar după consimțământul ei explicit (claim / link personal) |
| Permisiune clară | Ecran explicativ *înainte* de promptul nativ de Contacts, în limba userului |
| Funcționare fără permisiune | Aplicația trebuie să fie complet utilizabilă cu adăugare manuală. **Test obligatoriu înainte de submit.** |

> Refacerea modelului „shared Person data” din conversația inițială nu este exces de prudență — este condiția ca aplicația să existe.

### Guideline 5.1.2(i) — partajare cu AI terț (din 13 noiembrie 2025)
Apple cere **dezvăluire clară** și **permisiune explicită** înainte ca date personale să fie trimise unui furnizor AI terț.

**Ce facem:**
- ecran dedicat de consimțământ la prima folosire a recomandărilor AI, nu îngropat în ToS
- explicație în limbaj simplu: *ce* se trimite (interese, vârstă, buget — **nu** nume, telefon, note)
- aplicația funcționează și fără acest consimțământ (recomandări doar pe filtre, fără explicații AI)
- Privacy Nutrition Label completat corect

### Alte puncte de atenție
- **4.5.x / 5.1.1** — datele de contact nu pot fi folosite pentru marketing sau invitații nesolicitate. Linkul personal îl trimite **userul, manual**, din propriul messenger. **Aplicația nu trimite niciodată SMS/mesaje în numele lui.** Aceasta este o linie roșie.
- **1.x** — dacă permitem conținut generat (mesaje de felicitare AI), trebuie filtrare.
- **Account Deletion** — obligatoriu să existe ștergere de cont *în aplicație*, nu prin email.

### Google Play
Politici echivalente pentru User Data și permisiunea `READ_CONTACTS`: justificare în Data Safety, funcționalitate fără permisiune, fără transfer către terți nedeclarați.

---

## 3. Harta datelor

> Rezumat, după cod, nu după intenție. Registrul complet, cu destinatari și termene, e în `docs/20`; evaluarea de impact, în `docs/21`; răspunsurile pentru App Store și Google Play, în `docs/22`.

| Dată | Sursă | Unde stă | Temei | Retenție |
|---|---|---|---|---|
| Nume, email, parolă (hash) | userul | server | contract | cât contul; în copiile de siguranță încă ≤ 3 luni |
| Limbă, țară, fus orar, calendar, ziua userului | device / alegere | server | contract | idem |
| Nume și zi de naștere ale unui contact | agendă, **doar contactele selectate**, sau manual | server | interes legitim (uz personal) | până la ștergere — vezi nota de mai jos |
| **Numere de telefon, emailuri, adrese, poze din agendă** | — | **nu se citesc deloc** (D-017) | — | — |
| Relație, gen, buget, interese, ce să evite | userul / linkul public | server | interes legitim / consimțământ | până la ștergere |
| Note despre persoane | userul | server, criptat la rest | interes legitim | idem |
| Idei și istoric de cadouri | userul | server | interes legitim | idem |
| Token de push | device, cu permisiunea sistemului | server + Expo | contract | până la deconectare sau până când Expo îl respinge |
| Context trimis spre AI | derivat | **pseudonimizat**: coduri și titluri din catalog, fără text liber | consimțământ explicit, retras din Cont | nestocat de provider — **niciun provider activ azi** |
| Clickuri spre magazin | comportament | server | interes legitim | 24 luni, apoi agregat — **agregarea nu există încă** (`docs/21`, M-08) |
| Date din linkul public | **persoana însăși** | server | **consimțământ**, versionat | până la retragere, din linkul primit la final |
| Cereri din Faza 0 | vizitatorul landing-ului | server | consimțământ | **nedefinită** (`docs/21`, M-09) |

**Persoanele șterse** din aplicație rămân în baza de date (soft delete), ca reimportul din agendă să le readucă cu tot cu note (`ImportContacts`). Nu au termen de ștergere definitivă — decizia e în `docs/21`, M-07.
---

## 4. Fluxuri de consimțământ

```
Instalare
   ↓
[Alege limba: Română (default) / Русский / English]
   ↓
Cont (Apple / Google / email OTP)
   ↓
┌────────────────────────────────────────────────────┐
│ Ecran explicativ ÎNAINTE de promptul de Contacts:  │
│ "Citim doar numele și, dacă există, ziua de        │
│  naștere. Nu citim numere de telefon, emailuri     │
│  sau poze. Alegi tu pe cine urmărim.               │
│  Poți adăuga oameni și manual."                    │
│  [Permite acces]   [Adaug manual]                  │
└────────────────────────────────────────────────────┘
   ↓ (ambele rute continuă)
Selectezi persoanele pe care vrei să le urmărești
   ↓
Confirmi onomasticile deduse
   ↓
┌────────────────────────────────────────────────────┐
│ AHA MOMENT — lista cu ocazii găsite                │
└────────────────────────────────────────────────────┘
   ↓
"Să te anunțăm cu 7 zile înainte?"  → prompt push NATIV
   ↓
Prima cerere de recomandare
   ↓
┌────────────────────────────────────────────────────┐
│ Consimțământ AI:                                   │
│ "Ca să-ți dăm idei bune, trimitem către            │
│  furnizorul nostru AI: vârsta aproximativă,        │
│  genul, relația, ocazia, bugetul, interesele și    │
│  ce să evităm, cadourile din catalog oferite deja. │
│  NU trimitem: numele, telefonul, notele tale       │
│  sau alt text scris de tine."                      │
│  [Accept]   [Nu, vreau doar filtre]                │
└────────────────────────────────────────────────────┘
```

Acordul pentru AI se retrage oricând din **Cont → Inteligența artificială**, cu un singur comutator — la fel de ușor cum s-a dat.

---

## 5. Pagina publică de profil — reguli

- `noindex` implicit; userul alege dacă vrea indexare
- fără numere de telefon, fără email, fără adresă
- slug-ul nu este ghicibil (nu `/@ion`, ci slug ales + component random)
- rate limiting pe submisii; captcha la abuz
- textul de consimțământ arătat celui care completează se **versionează** și se stochează (`consent_text_version`) — aceasta e dovada consimțământului
- oricine a completat trebuie să poată cere ștergerea, fără cont

---

## 6. Securitate — minim obligatoriu

- `PEPPER` pentru HMAC în secrets manager, **nu** în `.env` din repo; rotația invalidează hash-urile (documentează procedura) — la MVP nu există hash-uri de telefon (D-017); IP-urile din formularele publice se păstrează ca HMAC cu `APP_KEY`
- notele despre persoane — criptate la rest (Laravel encrypted casts)
- sesiunile web criptate în producție (`SESSION_ENCRYPT=true`): la o eroare de validare, câmpurile din formularul public stau în sesiune până la cererea următoare
- rate limiting pe toate endpointurile publice
- fără PII în logurile Sentry (scrubbing configurat explicit)
- backup criptat, testat prin restaurare reală
- 2FA pe conturile Apple Developer, Google Play, hosting, domeniu

---

## 7. Checklist înainte de primul submit

- [ ] Privacy Policy + ToS, în RO / RU / EN, publicate la URL stabil — **texte gata** la `/legal/privacy` și `/legal/terms`, aliniate cu codul în S11.3; lipsesc operatorul (A7), revizuirea juridică și domeniul de producție
- [ ] Ecran de ștergere cont în aplicație, funcțional — **implementat (M7)**, verificat cap-coadă pe API; rămâne verificarea pe telefon
- [ ] Export de date funcțional — **implementat (M7)**, prin foaia de partajare a sistemului; rămâne verificarea pe telefon
- [ ] Privacy Nutrition Label (Apple) + Data Safety (Google) completate corect — **răspunsuri pregătite** în `docs/22`; se introduc la submit
- [ ] Aplicația testată **fără** permisiune de Contacts și **fără** push — complet utilizabilă — cod auditat (S11.8); rămâne testul pe telefon
- [ ] Consimțământ AI implementat și testat pe ruta „refuz” — testat pe API; retragerea din Cont adăugată în S11.3; rămâne verificarea pe telefon
- [ ] DPIA scrisă (chiar sumară — dar scrisă) — **ciornă** în `docs/21`; se aprobă după A7, cu juristul
- [ ] Registrul prelucrărilor completat — **ciornă** în `docs/20`; lipsește identitatea operatorului
- [ ] Contracte/DPA cu: hosting, push (Expo), email, AI provider, analytics, erori — lista și starea în `docs/20 § 2`
- [ ] Pagină web pentru cererea de ștergere a contului — Google Play o cere în Data Safety (`docs/22 § 2`)
- [ ] Cont de demo pentru App Review, cu date populate, cu instrucțiuni în EN
- [ ] Screenshot-uri și descriere în RO / RU / EN
