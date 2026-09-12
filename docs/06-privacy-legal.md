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

| Dată | Sursă | Unde stă | Temei | Retenție |
|---|---|---|---|---|
| Email / telefon user | userul | server | contract | cât contul + 30 zile |
| Locale (ro/ru/en) | device / alegere | server | contract | idem |
| Nume contact | agendă, **selectat de user** | server | interes legitim (uz personal) | până la ștergere |
| Ziua de naștere a unui contact | agendă / manual / de la persoană | server | interes legitim / consimțământ | idem |
| **Poza de contact** | agendă | **doar pe device** | — | nu părăsește device-ul |
| **Numere de telefon din agendă** | agendă | **niciodată în clar** — doar HMAC | interes legitim | hash, ireversibil |
| Note despre persoane | userul | server, criptat la rest | interes legitim | până la ștergere |
| Interese | manual / AI / link | server | interes legitim / consimțământ | idem |
| Istoric cadouri | userul | server | interes legitim | idem |
| Context trimis spre AI | derivat | **pseudonimizat**, zero-retention | consimțământ explicit | nestocat de provider |
| Clickuri spre magazin | comportament | server | interes legitim | 24 luni, apoi agregat |
| Date din linkul public | **persoana însăși** | server | **consimțământ** | până la retragere |

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
│  naștere. Numerele nu ajung niciodată la noi în    │
│  clar. Pozele rămân pe telefonul tău.              │
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
│  furnizorul nostru AI: vârstă aproximativă,        │
│  relația, interesele și bugetul.                   │
│  NU trimitem: numele, telefonul, notele tale."     │
│  [Accept]   [Nu, vreau doar filtre]                │
└────────────────────────────────────────────────────┘
```

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

- `PEPPER` pentru HMAC în secrets manager, **nu** în `.env` din repo; rotația invalidează hash-urile (documentează procedura)
- notele despre persoane — criptate la rest (Laravel encrypted casts)
- rate limiting pe toate endpointurile publice
- fără PII în logurile Sentry (scrubbing configurat explicit)
- backup criptat, testat prin restaurare reală
- 2FA pe conturile Apple Developer, Google Play, hosting, domeniu

---

## 7. Checklist înainte de primul submit

- [ ] Privacy Policy + ToS, în RO / RU / EN, publicate la URL stabil
- [ ] Ecran de ștergere cont în aplicație, funcțional — **implementat (M7)**, verificat cap-coadă pe API; rămâne verificarea pe telefon
- [ ] Export de date funcțional — **implementat (M7)**, prin foaia de partajare a sistemului; rămâne verificarea pe telefon
- [ ] Privacy Nutrition Label (Apple) + Data Safety (Google) completate corect
- [ ] Aplicația testată **fără** permisiune de Contacts și **fără** push — complet utilizabilă
- [ ] Consimțământ AI implementat și testat pe ruta „refuz”
- [ ] DPIA scrisă (chiar sumară — dar scrisă)
- [ ] Registrul prelucrărilor completat
- [ ] Contracte/DPA cu: hosting, AI provider, analytics, push
- [ ] Cont de demo pentru App Review, cu date populate, cu instrucțiuni în EN
- [ ] Screenshot-uri și descriere în RO / RU / EN
