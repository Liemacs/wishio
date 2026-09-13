# 22 — Etichetele de confidențialitate: App Store și Google Play

> Răspunsurile pentru **App Privacy** (App Store Connect) și **Data safety** (Play Console), pregătite pe 13 septembrie 2026 din cod, nu din intenție. Apple și Google compară etichetele cu ce face aplicația, iar o nepotrivire înseamnă respingere sau, mai târziu, scoaterea din store. Registrul complet: `docs/20`.
>
> **Se reverifică** la fiecare release care adaugă un SDK sau o dată nouă — lista din § 4.

---

## 0. Ce contează ca „colectat”

- **Apple**: datele care pleacă de pe telefon și se păstrează mai mult decât durează cererea. Include ce introduce utilizatorul despre alți oameni. „Legat de utilizator” înseamnă asociat contului — la noi, tot.
- **Google**: la fel. „Partajat” înseamnă transmis unui terț; nu intră aici furnizorii care prelucrează în numele nostru (găzduire, Expo, email, AI) și nici ce publică utilizatorul singur, prin linkul lui.
- **Tracking** (Apple): combinarea datelor noastre cu date ale altor companii, pentru reclame, sau transmiterea lor către brokeri de date. **Nu facem.** Nu folosim identificatorul de publicitate, deci nu cerem permisiunea App Tracking Transparency.

---

## 1. Apple — App Privacy

**Colectați date din această aplicație?** Da.

| Tip (în App Store Connect) | Ce anume | Scopuri | Legat de utilizator | Tracking |
|---|---|---|---|---|
| Contact Info → **Name** | numele contului | App Functionality | da | nu |
| Contact Info → **Email Address** | emailul contului; rezumatul săptămânal | App Functionality | da | nu |
| **Contacts** | contactele bifate din agendă: nume și zi de naștere | App Functionality | da | nu |
| User Content → **Other User Content** | persoanele adăugate (relație, gen, zi de naștere, interese, note), ideile, lista de dorințe, mesajele primite prin linkul public | App Functionality, Product Personalization | da | nu |
| Identifiers → **User ID** | id-ul contului | App Functionality | da | nu |
| Identifiers → **Device ID** | tokenul de push | App Functionality | da | nu |
| Purchases → **Purchase History** | cadourile marcate „oferit” sau „cumpărat”, cu suma | App Functionality | da | nu |
| Usage Data → **Product Interaction** | ofertele deschise spre magazine | Analytics | da | nu |
| **Other Data Types** | ziua de naștere a utilizatorului | App Functionality | da | nu |

**Nu colectăm:** Health & Fitness, Financial Info, Location, Sensitive Info, Emails or Text Messages, Photos or Videos, Audio Data, Gameplay Content, Customer Support, Browsing History, Search History (căutările nu se stochează), Advertising Data, Other Usage Data, Diagnostics (până la Sentry — § 4).

**Privacy Policy URL:** `https://wishio.md/legal/privacy` — valid după deploy; acceptă `?lang=ro`, `ru` sau `en`.

**Alte cerințe Apple, verificate pe cod:**
- ștergerea contului din aplicație (5.1.1(v)) — Cont → Șterge contul;
- acord explicit înainte ca datele să plece la un AI terț (5.1.2(i)) — foaia de acord, retragere din Cont; niciun furnizor activ;
- textul permisiunii de Contacts, în RO/RU/EN, spune exact ce citim: numele și ziua de naștere, doar pentru contactele alese;
- aplicația funcționează fără Contacts și fără push — S11.8;
- **manifestul de confidențialitate** (`PrivacyInfo.xcprivacy`): modulele Expo își aduc motivele pentru API-urile care le cer. Dacă la primul build de producție App Store Connect trimite avertismentul ITMS-91053, motivele lipsă se adaugă în `ios.privacyManifests` din `app.json`.

---

## 2. Google Play — Data safety

| Întrebare | Răspuns |
|---|---|
| Colectați sau partajați date? | Da |
| Toate datele sunt criptate în tranzit? | Da — cu condiția ca build-ul de producție să vorbească cu API-ul doar prin HTTPS (în dezvoltare, API-ul local merge pe HTTP) |
| Metode de creare a contului | email și parolă |
| Utilizatorii pot cere ștergerea datelor? | Da, din aplicație |
| Link pentru ștergerea contului | **nu există încă** — vezi mai jos |

**Linkul pentru ștergerea contului** e obligatoriu în Play Console pentru aplicațiile cu cont: o pagină web de unde utilizatorul poate cere ștergerea fără să reinstaleze aplicația, cu pașii și cu ce se păstrează. Propunere: `wishio.md/legal/delete-account`, în RO/RU/EN, cu pașii din aplicație și cu adresa de contact din `docs/21`, M-13, pentru cine nu mai are aplicația. **Blochează submit-ul pe Android.**

| Categorie → tip | Ce anume | Partajat | Opțional | Scopuri |
|---|---|---|---|---|
| Personal info → **Name** | numele contului | nu | nu | App functionality, Account management |
| Personal info → **Email address** | emailul contului | nu | nu | App functionality, Account management |
| Personal info → **User IDs** | id-ul contului | nu | nu | App functionality, Account management |
| Personal info → **Other info** | ziua de naștere a utilizatorului; relația, genul și ziua de naștere ale persoanelor adăugate | nu | da | App functionality, Personalization |
| Contacts → **Contacts** | contactele bifate: nume și zi de naștere | nu | da | App functionality |
| Financial info → **Purchase history** | cadourile marcate „oferit” sau „cumpărat” | nu | da | App functionality |
| App activity → **App interactions** | ofertele deschise spre magazine | nu | nu | Analytics |
| App activity → **Other user-generated content** | note, interese, idei, lista de dorințe, mesajele primite prin linkul public | nu | da | App functionality, Personalization |
| Device or other IDs → **Device or other IDs** | tokenul de push | nu | da | App functionality |

Toate tipurile de mai sus sunt colectate și niciunul nu e prelucrat doar efemer: toate se stochează.

**Nu colectăm:** Location, Health and fitness, Messages, Photos and videos, Audio, Files and docs, Calendar, Web browsing, In-app search history, Installed apps, App info and performance (până la Sentry — § 4).

**Permisiuni Android**, verificate cu `npx expo config --type introspect`:
- `READ_CONTACTS` — importul contactelor alese; ecranul explicativ vine înaintea promptului sistemului, iar aplicația merge și fără;
- `INTERNET`, `VIBRATE` — standard;
- `SYSTEM_ALERT_WINDOW` — vine din șablonul Expo, pentru meniul de dezvoltare; de verificat că lipsește din build-ul de producție, altfel se blochează la fel ca cele de mai jos;
- **blocate explicit** (`android.blockedPermissions`): `WRITE_CONTACTS` — nu scriem în agendă; `READ_EXTERNAL_STORAGE`, `WRITE_EXTERNAL_STORAGE` — exportul trece prin foaia de partajare.

Permisiunile aduse de bibliotecile native (de exemplu cele pentru notificări) se adaugă la build, prin fuziunea manifestelor. Lista finală se citește în Play Console → App bundle explorer, după primul build de producție.

---

## 3. Paginile web nu intră în etichete

Landing-ul și paginile `/@slug` colectează date prin browser, nu prin aplicație, deci nu apar în etichetele de mai sus. Le acoperă politica de confidențialitate și `docs/20` (P4, P8, P9).

---

## 4. Când se schimbă etichetele

| Schimbare | Ce se modifică |
|---|---|
| Sentry în aplicație (S11.7) | Apple: Diagnostics → Crash Data, Performance Data — App Functionality, nelegate de utilizator dacă nu trimitem id-ul. Google: App info and performance → Crash logs, Diagnostics |
| Un furnizor de AI activ | nicio categorie nouă, cât timp furnizorul prelucrează doar în numele nostru, prin contract (`docs/21`, M-15) |
| PostHog sau alt SDK de analytics în aplicație | Apple: Usage Data, eventual Identifiers. Google: App activity, Device or other IDs. Plus revizuirea DPIA |
| Numere de telefon, chiar ca hash (dacă D-017 se schimbă) | Apple: Contacts, descris din nou. Google: Contacts. Plus revizuirea DPIA |
| Căutări salvate | Apple: Search History. Google: In-app search history |
| Plăți în aplicație | Apple: Financial Info. Google: Financial info |
