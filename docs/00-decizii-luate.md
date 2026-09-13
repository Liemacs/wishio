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

---

## D-010 · Ritm de lucru — 15 ore/săptămână
**Data:** 2026-09-12 · **Stare:** confirmată

**Consecință directă: MVP-ul este de ~16 săptămâni, nu 12.** `PLAN.md` recalibrat.

Recomandare de organizare: două seri fixe + o jumătate de zi în weekend, puse în calendar ca întâlniri. Ritmul susținut bate sprinturile urmate de pauze — un produs cu remindere se construiește din multe piese mici, nu din eroism.

## D-011 · Design — solo + Claude, pe biblioteci
**Data:** 2026-09-12 · **Stare:** confirmată

Fără designer extern. Calitatea vizuală vine din **biblioteci mature, folosite corect**, nu din componente scrise de la zero.

**Regula:** nu reimplementăm manual nimic ce o bibliotecă matură rezolvă mai bine — bottom sheets, liste performante, animații, tranziții, feedback tactil. `src/components/ui/` conține doar **compunerea** lor peste token-urile Wishio, nu reimplementări.

Stack-ul ales și verificat: `docs/05 § 1.1`.

## D-012 · Stack de UI și animație — instalat și verificat pe build
**Data:** 2026-09-12 · **Stare:** confirmată

Toate verificate cu Reanimated 4.5.1 / React 19.2.3 / RN 0.86.3 / Expo SDK 57, build-ul trece.

| Bibliotecă | Versiune | Rol |
|---|---|---|
| `react-native-reanimated` | 4.5.1 | motorul de animație, pe UI thread |
| `moti` | 0.30 | animații declarative peste Reanimated — `from` / `animate` |
| `@gorhom/bottom-sheet` | 5.2 | standardul pentru sheet-uri; suportă Reanimated 4 |
| `@shopify/flash-list` | 2.3 | liste performante (persoane, produse, ocazii) |
| `lottie-react-native` | 7.3 | animații de stare goală și momente de sărbătoare |
| `expo-haptics` | 57 | feedback tactil — cel mai ieftin câștig de calitate percepută |
| `expo-blur`, `expo-linear-gradient` | 57 | profunzime vizuală |
| `expo-image` | 57 | imagini cu tranziții și cache — esențial pentru catalog |
| `expo-symbols` | 57 | SF Symbols pe iOS |

**Verificat pe build:** Moti, Reanimated, LinearGradient și Haptics, folosite împreună în `app/index.tsx`.

**Opțional, când e nevoie:** `@shopify/react-native-skia` pentru grafică avansată. Nu se instalează preventiv — adaugă greutate semnificativă.

---

## D-013 · Faza 0 se amână; construim produsul
**Data:** 2026-09-12 · **Stare:** confirmată

Validarea concierge din `docs/14` nu se execută acum. Landing-ul rămâne construit și funcțional local, gata de pornit când va fi cazul.

**Consecințe, asumate:**
- Porțile G0–G2 nu se măsoară înainte de a construi. Riscul rămâne cel din `docs/02`: se poate construi ceva ce nu are cerere.
- **Corpusul pentru prompturi (C8) nu există.** La S7, criteriile AI se vor scrie din intuiție, nu din 20 de conversații reale. Se compensează prin setul de evaluare și prin calibrare după lansare.

**De reținut:** landing-ul poate fi pornit oricând, în paralel cu dezvoltarea. Dacă la un moment dat apare o săptămână liberă, cele 20 de conversații rămân cel mai ieftin mod de a valida — și singurul mod de a obține corpusul.

## D-014 · Găzduire — local deocamdată
**Data:** 2026-09-12 · **Stare:** confirmată

Dezvoltare locală: MariaDB prin XAMPP, `php artisan serve`, Expo Go. VPS-ul și `wishio.md` vin mai târziu. `docker/compose.yaml` și planul din `docs/10` rămân valabile pentru momentul acela.

PostHog nu se configurează acum — analytics-ul e deja scris ca opțional (`docs/17 § 7`), pagina funcționează identic fără cheie.

## D-015 · Zilele de naștere — din contacte dacă există, altfel manual
**Data:** 2026-09-12 · **Stare:** confirmată

Importul citește ziua de naștere din agendă acolo unde există; unde nu, utilizatorul o adaugă manual. Măsurătoarea B1 (câte contacte au efectiv ziua completată) nu se mai face în avans.

**Consecință de design, importantă:** ecranul O8 („am găsit N ocazii") **nu poate presupune un rezultat**. Se adaptează la ce a găsit efectiv:

| Ce a găsit | Ce arată |
|---|---|
| multe zile de naștere | „Am găsit 18 zile de naștere" |
| puține, dar multe onomastici | „Am găsit 4 zile de naștere și 112 onomastici" |
| nimic | duce direct la adăugare manuală rapidă, fără să pară un eșec |

Ecranul adaptiv e oricum soluția mai bună decât a ghici în avans distribuția. Vezi `docs/09 § O8`.

## D-016 · Catalogul Magaziner — implementare amânată
**Data:** 2026-09-12 · **Stare:** confirmată

API-ul nu există încă. `CatalogAdapter` rămâne abstract; se implementează `MagazinerCatalog` când apare. Până atunci, dezvoltarea merge pe `ManualCatalog` cu un set mic de produse, suficient pentru a construi și testa motorul de recomandări.

Nimic din S3–S5 (persoane, contacte, ocazii, remindere) nu depinde de catalog, deci amânarea nu blochează drumul critic.

---

## D-017 · MVP nu colectează deloc numere de telefon
**Data:** 2026-09-12 · **Stare:** confirmată

Importul din contacte citește **doar numele și, dacă există, ziua de naștere**. Numărul de telefon nu se citește, nu se transmite și nu se stochează — nici măcar sub formă de hash.

**De ce, deși `docs/04 § 2` descria un `contact_hash`:**

Hash-ul avea un singur rol — legarea unei persoane de contul ei când se înregistrează („claim"), ca să-și poată confirma datele. Acea funcție **nu e în scopul MVP-ului** (`docs/03`). Până atunci, hash-ul n-ar fi servit la nimic, dar ar fi trebuit apărat.

Și mai important, schema n-ar fi fost atât de curată pe cât părea. Ca serverul să poată potrivi contactele a doi utilizatori, trebuie să poată calcula aceeași valoare din număr. Deci ori serverul vede numărul, ori clientul cunoaște cheia. Un SHA-256 pe un număr de telefon e reversibil prin forță brută — spațiul de căutare e mic. Protecția ar fi fost **operațională** (nu-l stocăm, nu-l logăm), nu criptografică.

Cea mai onestă variantă este să nu-l colectăm deloc cât timp nu ne trebuie.

**Consecințe:**
- Coloana `contact_hash` rămâne, nefolosită, pentru momentul în care apare claim-ul.
- Re-sincronizarea se face pe `device_contact_id`, identificatorul local al contactului — nu părăsește dispozitivul ca date personale și nu spune nimic despre persoană.
- Textul permisiunii poate spune adevărul simplu: *„citim doar numele și ziua de naștere"*. Nu mai avem nevoie de formulări despre numere care „nu ajung la noi în clar".
- Când vom implementa claim-ul, decizia se redeschide, cu o evaluare de impact scrisă (`docs/06`).


---

## D-018 · Sărbătorile nu aparțin unei persoane
**Data:** 2026-09-12 · **Stare:** confirmată

8 Martie nu e „a Anei” — e o dată cu un **public**. Modelul reflectă asta: o ocazie de tip sărbătoare are `person_id = null` și un `holiday_id`.

**Consecința de UX, care e chiar motivul deciziei:** o sărbătoare produce **o singură notificare**, nu una per persoană. *„Ziua Femeii e peste 3 zile — 12 persoane pe lista ta"* în loc de douăsprezece notificări separate, care ar fi zgomot garantat și ar încălca regula anti-spam din prima zi.

**Publicul se calculează la afișare, nu se stochează.** Lista de contacte se schimbă; una înghețată ar deveni greșită fără să observe nimeni. Regulile sunt simple — femei, bărbați, copii, partener — și sunt un filtru de comoditate, nu o restricție: utilizatorul vede lista și poate cumpăra pentru oricine.

Pentru „copii" ne bazăm pe **relație înaintea vârstei**: vârsta lipsește foarte des, relația nu.

**Paștele se calculează, nu se stochează.** E mobil, iar o dată greșită ar muta cea mai mare sărbătoare din an. Algoritmul lui Meeus pentru calendarul iulian, plus decalajul de 13 zile — verificat pe 2024 (5 mai), 2025 (20 aprilie), 2026 (12 aprilie), 2027 (2 mai).

⚠️ Datele celorlalte sărbători se verifică cu un calendar oficial înainte de lansare, la fel ca onomasticile (`docs/15 § 4`).


---

## D-019 · Editarea manuală bate ierarhia de încredere
**Data:** 2026-09-12 · **Stare:** confirmată · *corectează D-004*

`docs/04 § 3` spunea că `subject_provided` depășește `owner_manual`. Logic, dar greșit în practică: dacă Ana își completează numele prin linkul public ca „Ana Casianov", Maxim nu-și mai putea redenumi propriul contact în „Ana ❤️". Aplicația îi ignora editarea **în tăcere**.

**Regula corectată:** ierarhia guvernează scrierile **automate** — sincronizarea agendei, deducerile, completările de pe pagina publică. O acțiune explicită a proprietarului trece întotdeauna, și de atunci câmpul e protejat de orice sursă automată.

Contactul e al lui, iar vederea lui asupra contactului e a lui. Un sistem care „știe mai bine" decât utilizatorul despre propriile lui date e un sistem pe care oamenii îl abandonează.

## D-020 · Paginile publice se fac în Blade, nu Inertia + Vue
**Data:** 2026-09-12 · **Stare:** confirmată · *ajustează `docs/05 § 1`*

`docs/05` prevedea Inertia + Vue pentru web-ul public. Paginile sunt însă aproape statice — un formular și trei ecrane de stare — iar landing-ul era deja în Blade.

Inertia ar fi însemnat un al doilea strat de build, un al doilea sistem de rutare și un bundle JS pentru pagini care nu au nevoie de el. Blade le servește server-rendered, indexabile și fără JavaScript obligatoriu.

**Se reconsideră** dacă merchant dashboard-ul (v2) cere interactivitate reală — acolo Inertia își merită costul.

## D-021 · Numele nu leagă date între utilizatori
**Data:** 2026-09-12 · **Stare:** confirmată · *aplică D-004*

**Propunerea:** dacă un contact al meu are același nume ca un contact al altui utilizator, aplicația îmi completează ziua de naștere, poza și restul — după ce confirm că e aceeași persoană, sau aleg dintre mai mulți cu același nume.

**Nu se face.** Schimbă cheia de potrivire, numele în loc de număr, dar nu și fluxul interzis de D-004: datele Anei, introduse de Maxim, ajung la Ion fără ca Ana să știe.

- **Confirmarea lui Ion nu e consimțământul Anei.** Legea 195/2024 cere un temei ca datele Anei să-i fie dezvăluite lui Ion. Consimțământul îl poate da doar ea, iar interesul legitim nu trece testul de echilibrare (`docs/02 § R2`). Wishio ar mai trebui s-o și informeze pe Ana că îi prelucrează date primite de la altcineva — și nu are cum s-o contacteze.
- **E mai rău decât cu numărul, pentru că numele nu sunt unice.** Ca Ion să aleagă dintre omonimi, aplicația trebuie să-i arate ceva despre fiecare — ziua, poza, vârsta —, adică date despre străini. Oricine ar adăuga un contact „Maria Popescu” ar vedea zilele de naștere ale tuturor Mariilor Popescu din agendele altora. Asta e căutare de persoane, interzisă de regula 9 și de `docs/04 § 2`.
- **Poza de contact nu părăsește telefonul** (regula 4), deci nu are de unde veni.
- **App Store 5.1.2** respinge exact compilarea de date despre terți din alte surse decât persoana însăși.

**Ce păstrăm din idee:** confirmarea „cine este?” și alegerea dintre omonimi, aplicate completărilor din linkul public. Acolo datele vin de la persoana însăși, cu consimțământ versionat, iar candidații sunt doar contactele proprietarului (`PLAN.md` S9.8).

**Descoperit cu ocazia asta:** `AcceptSubmission` lipește azi completarea de primul contact cu același prenume, fără confirmare. „Ana Popescu” poate suprascrie numele și ziua lui „Ana Rusu” importată din agendă, iar retragerea completării șterge definitiv acel contact, cu ocaziile lui, dacă proprietarul nu l-a editat manual (`PLAN.md` S9.7).

**Rezolvat în S9.7:** o completare se leagă doar de persoana apărută dintr-o completare anterioară a aceluiași om — același link, același nume complet. Retragerea nu mai șterge un contact care exista înainte; îi scoate datele trimise de persoană, ca agenda să le poată reface.

## D-022 · Producția pe un VPS în UE, administrat cu Forge sau Ploi
**Data:** 2026-09-13 · **Stare:** confirmată · *completează `docs/10 § 2`*

**Decizia:** serverul de producție e un VPS în UE (Hetzner sau similar), administrat printr-un panou — Laravel Forge sau Ploi. Nu un server configurat de mână și nici o platformă PaaS.

**De ce:**
- Arhitectura rămâne cea din `docs/10 § 2` — Nginx, PHP 8.3, MySQL, Redis, un singur server —, dar HTTPS-ul, worker-ul, planificarea, backup-ul bazei și deploy-ul le face panoul. La 15 ore pe săptămână (D-010), fiecare script de server scris de mână e încă un lucru de întreținut.
- Scripturile pentru un server configurat de mână nu pot fi încercate înainte să existe serverul, iar prima lor rulare ar fi chiar pe producție.
- O platformă PaaS scapă de server, dar costul crește cu traficul, iar locația datelor cere mai multă atenție.

**Consecințe:**
- Panoul are acces de administrator la server, deci e împuternicit: intră în registru (`docs/20 § 2`), cu DPA.
- Cozile rulează cu `queue:work` pe Redis, ca daemon în panou. Horizon, prevăzut în `docs/10 § 2`, se adaugă când va fi nevoie de panoul lui.
- Producția se actualizează dintr-un tag `v*`, după ce trec testele din CI (`docs/25 § 5`).
- Domeniul `wishio.md` nu e cumpărat încă: e primul pas (`docs/25 § 1`).
