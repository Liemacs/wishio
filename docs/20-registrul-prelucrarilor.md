# 20 — Registrul prelucrărilor

> **Ciornă de lucru, 13 septembrie 2026.** Registrul de evidență cerut de Legea nr. 195/2024 (echivalentul art. 30 din GDPR, pe care legea îl transpune). Se completează cu identitatea operatorului după decizia A7 (`docs/08`) și se revizuiește de un jurist înainte de lansare. Trimiterile la articole sunt la GDPR; numerotarea din legea națională o confirmă juristul.
>
> Regula documentului: fiecare rând descrie ce face **codul de azi**. Unde codul și intenția diferă, scrie explicit și trimite la măsura din `docs/21 § 6`.

---

## 0. Operatorul

| | |
|---|---|
| Operator | **de completat după A7** — companie sau persoană fizică |
| Contact pentru protecția datelor | de completat: o adresă dedicată, publicată în politica de confidențialitate (`docs/21`, M-13) |
| Responsabil cu protecția datelor (DPO) | nu pare obligatoriu la lansare — argumentul în `docs/21 § 7` |
| Reprezentant în UE | necesar abia când serviciul se adresează utilizatorilor din UE (România) — `docs/06 § 1` |
| Autoritatea de supraveghere | Centrul Național pentru Protecția Datelor cu Caracter Personal (CNPDCP) |
| Unde rulează | deocamdată doar local; producția, pe un VPS în UE (`docs/10 § 2`), nu există încă |

---

## 1. Activitățile de prelucrare

### P1 · Contul utilizatorului

| | |
|---|---|
| Scop | crearea contului, autentificarea, preferințele de funcționare |
| Persoane vizate | utilizatorii |
| Date | nume, email, parola (hash bcrypt), limbă, țară, fus orar, calendarul onomasticilor, ziua de naștere (opțional), momentul acordului pentru AI și momentul în care l-am cerut, tokenuri de acces (Sanctum, stocate ca hash) |
| Sursă | utilizatorul |
| Temei | executarea contractului — art. 6 alin. (1) lit. b |
| Destinatari | furnizorul de găzduire (împuternicit) |
| Retenție | cât există contul. Ștergerea contului e imediată și definitivă (`DeleteAccount`); copiile de siguranță mai păstrează datele cel mult 3 luni. Tokenurile de acces nu expiră (`sanctum.expiration = null`): dispar la deconectare sau odată cu contul — `docs/21`, M-11 |
| În cod | `users`, `personal_access_tokens` · `AuthController`, `AccountController`, `DeleteAccount`, `ExportUserData` |

### P2 · Persoanele urmărite de utilizator

| | |
|---|---|
| Scop | amintirea ocaziilor (zile de naștere, onomastici, aniversări) și pregătirea cadourilor, în beneficiul personal al utilizatorului |
| Persoane vizate | oameni din anturajul utilizatorului — rude, prieteni, colegi, **posibil minori**. De regulă nu sunt utilizatori și nu știu că apar în aplicație |
| Date | numele afișat și prenumele normalizat (pentru onomastică), identificatorul local al contactului din telefon, relația, genul, data nașterii (cu sau fără an), bugetul, notele (**criptate la rest**), interesele și ce să evite (coduri din listă și, opțional, un text scurt), ocaziile, ideile și istoricul de cadouri, proveniența fiecărui câmp |
| Ce NU colectăm | numere de telefon, emailuri, adrese, fotografii (D-017). Schema are coloanele `people.contact_hash`, `people.avatar_path`, `people.claimed_user_id` și `users.phone_hash`, dar nu le scrie nimic — `docs/21`, M-19 |
| Sursă | utilizatorul: manual sau din agenda telefonului, **doar contactele pe care le bifează** (pleacă id-ul local, numele și data nașterii); dedus: onomastica, din prenume; persoana însăși: P4 |
| Temei | interesul legitim al utilizatorului și al operatorului — art. 6 alin. (1) lit. f; testul de echilibrare în `docs/21 § 4` |
| Informarea persoanelor | nu avem cum să le contactăm, pentru că nu colectăm date de contact — excepția de la art. 14 alin. (5) lit. b, cu politica publică drept măsură; de validat de jurist |
| Destinatari | găzduirea; furnizorul de notificări (numele persoanei și ocazia, în textul notificării); furnizorul de email (numele și ocaziile, în rezumatul săptămânal); furnizorul de AI, doar context pseudonimizat și doar cu acordul utilizatorului (P5) |
| Retenție | până când utilizatorul șterge persoana sau contul. **Ștergerea unei persoane e logică (soft delete)**: rândul rămâne, ca un reimport din agendă să o readucă cu tot cu note (`ImportContacts`), și nu are termen — `docs/21`, M-07 |
| În cod | `people`, `person_field_sources`, `person_interests`, `person_avoids`, `occasions`, `gift_history`, `gift_ideas` · `ImportContacts`, `WritePersonField`, `PersonController` |

Cererea de import mai poartă două numere agregate: câte contacte are agenda și câte au zi de naștere. Serverul le validează și nu le păstrează.

### P3 · Remindere: notificări push și rezumatul pe email

| | |
|---|---|
| Scop | anunțarea ocaziilor la timp |
| Persoane vizate | utilizatorii; indirect, persoanele din P2 și cei care au completat linkul (P4), prin numele din notificare |
| Date | tokenul Expo de push, platforma, limba, ultima activitate; setările (zile înainte, ora preferată, orele de liniște, push și email pornite sau oprite); coada notificărilor (ocazia, zilele înainte, momentul programat, trimis, eroarea, momentul în care omul a apăsat reminderul). **Textul notificării nu se stochează**: se compune la trimitere |
| Ce se trimite | push: un titlu cu numele persoanei și ocazia („Ana are ziua mâine”) sau, la completări, cu numele celui care a completat; date de rutare (tipul, id-ul reminderului și al persoanei sau al completării). Email: numele utilizatorului, numele persoanelor și ocaziile săptămânii |
| Sursă | telefonul (tokenul), utilizatorul (setările), P2 (conținutul) |
| Temei | executarea contractului; permisiunea de notificări a sistemului e condiția tehnică, nu temeiul. Politica publicată trece notificările la „consimțământ” — de aliniat cu juristul |
| Destinatari | Expo (650 Industries, Inc., SUA), care livrează prin Apple Push Notification service și Firebase Cloud Messaging (SUA); furnizorul SMTP, încă neales |
| Transferuri | SUA, prin Expo, Apple și Google — mecanismul de transfer se documentează la contractare (§ 2) |
| Retenție | tokenul: până la deconectare (aplicația îl retrage), până la ștergerea contului sau până când Expo îl declară invalid (`SendDueNotifications` îl șterge). Coada: cât contul — `docs/21`, M-11. Rezumatul se oprește din setări sau din linkul semnat de dezabonare |
| În cod | `device_tokens`, `user_settings`, `notifications_queue` · `SendDueNotifications`, `NotifyOwnersOfPendingSubmissions`, `ExpoPushSender`, `SendWeeklyDigests` |

### P4 · Linkul public și completările

| | |
|---|---|
| Scop | (a) utilizatorul publică, dacă vrea, o pagină cu numele și lista de dorințe; (b) oamenii își trimit singuri ziua de naștere și interesele către el |
| Persoane vizate | (a) utilizatorul; (b) vizitatorii care completează formularul, fără cont |
| Date (a) | numele afișat, slug-ul, vizibilitatea pe câmpuri, lista de dorințe (titlu, link, notă, prioritate, vizibilitate), numărul de vizualizări — fără cookie și fără identificarea vizitatorului |
| Date (b) | nume, data nașterii, interese (coduri), un mesaj de cel mult 280 de caractere, limba, versiunea și momentul consimțământului, tokenul de ștergere, HMAC-SHA256 al IP-ului (cu cheia aplicației), momentele acceptării și ale confirmării identității |
| Temei | (a) executarea contractului; (b) consimțământ — art. 6 alin. (1) lit. a: bifă obligatorie, text versionat (`2026-09-1`), cu link spre politică lângă bifă |
| Destinatari | (b) utilizatorul căruia îi aparține linkul — acesta e scopul; găzduirea; furnizorul de notificări (numele celui care a completat, în notificarea „cine este?”) |
| Retenție | (b) până la retragere, din linkul primit la final, fără cont (`WithdrawSubmission`), sau până la ștergerea contului proprietarului. Completările la care proprietarul nu răspunde nu expiră — `docs/21`, M-10 |
| Măsuri | `noindex` pe toate paginile publice (setarea `is_indexable` nu are efect azi — M-20); slug cu sufix aleator; cel mult 10 trimiteri în 10 minute de la același IP; nicio dată de contact pe pagină; completarea nu se lipește de un contact existent fără decizia proprietarului (S9.7, S9.8) |
| În cod | `public_profiles`, `wishlist_items`, `profile_submissions` · `PublicProfileController`, `MyProfileController`, `SubmissionController`, `ReceiveSubmission`, `WithdrawSubmission` |

### P5 · Recomandări de cadouri

| | |
|---|---|
| Scop | produse din catalog potrivite pentru o persoană, o ocazie și un buget |
| Date | contextul persoanei (P2), bugetul, ocazia; rezultatul: criteriile (coduri de interese, prețuri), produsele alese, scorurile, explicațiile |
| Profilare | da, în sens larg: gusturile unei persoane sunt evaluate ca să fie alese produse. Nicio decizie cu efecte juridice sau similare — nu intră sub art. 22 |
| Temei | executarea contractului, pentru recomandările calculate pe server; **consimțământ explicit** pentru trimiterea contextului către un furnizor de AI extern — art. 6 alin. (1) lit. a și Apple 5.1.2(i). Acordul se dă în foaia de la prima recomandare și se retrage din **Cont → Inteligența artificială** |
| Ce ar pleca spre AI | intervalul de vârstă, genul, relația, ocazia, bugetul, codurile de interese și de evitat, titlurile din catalog ale cadourilor oferite deja. **Nu pleacă**: nume, note, „de evitat” scris liber, titluri de cadouri introduse de mână (`RecommendationsTest`) |
| Destinatari | **niciun furnizor de AI activ azi**: `WISHIO_AI_PROVIDER=null`, există doar `RuleBasedAiProvider`, care rulează pe server. Condițiile de activare: `docs/21`, M-15 |
| Retenție | rulările și rezultatele rămân cât persoana (ștergere în cascadă) |
| În cod | `recommendation_runs`, `recommendation_items`, `users.ai_consent_at` · `GenerateRecommendations`, `PersonContext`, `RecommendationController` |

### P6 · „Spune-mi despre…” — interese din text

| | |
|---|---|
| Scop | propune interese dintr-o descriere liberă; utilizatorul bifează ce e corect |
| Date | textul, cel mult 2 000 de caractere |
| Cum | potrivire de cuvinte-cheie, pe server, în memorie. **Textul nu se stochează, nu se loghează și nu pleacă spre AI** |
| Temei | interesul legitim (P2) |
| Retenție | cât durează cererea |
| În cod | `PersonAnalysisController`, `MatchInterestsFromText` |

### P7 · Clickuri spre magazine

| | |
|---|---|
| Scop | deschiderea ofertei; măsurarea utilității sugestiilor (G5, `docs/07`); mai târziu, raportare agregată către comercianți |
| Date | utilizatorul, oferta, comerciantul, persoana pentru care căuta (opțional), prețul, contextul (căutare, recomandare, listă de dorințe, idee), momentul |
| Temei | interesul legitim — art. 6 alin. (1) lit. f |
| Destinatari | niciunul, la nivel individual. Linkul deschis e linkul fix al ofertei, fără identificatori ai utilizatorului; ce colectează magazinul după aceea ține de politica lui |
| Retenție | politica promite 24 de luni, apoi doar agregat. **Agregarea nu există încă** — `docs/21`, M-08 |
| În cod | `outbound_clicks` · `ProductController::click` |

Căutările de produse nu se stochează: termenul căutat filtrează catalogul și atât.

### P8 · Cererile din Faza 0 (landing)

| | |
|---|---|
| Scop | validarea produsului: oamenii descriu pe cine au de sărbătorit, iar ideile se caută manual |
| Persoane vizate | vizitatorii landing-ului; indirect, persoanele descrise în textul liber |
| Date | limba, relația, intervalul de vârstă și genul destinatarului, descrierea liberă, bugetul, ocazia și data, contactul (Telegram, WhatsApp sau email) și canalul, versiunea și momentul consimțământului, răspunsul trimis, feedbackul, măsurătorile G1, sursa vizitei, HMAC al IP-ului |
| Temei | consimțământ; textul de lângă bifă promite ștergerea la cerere |
| Destinatari | operatorul; canalul prin care se răspunde (Telegram, WhatsApp, email) |
| Retenție | **nedefinită**, iar `wishio:requests` nu are o comandă de ștergere — `docs/21`, M-09 |
| În cod | `gift_requests` · `LandingController`, `GiftRequestsCommand` |

### P9 · Analytics pe site — inactiv

| | |
|---|---|
| Stare | **inactiv**: PostHog se încarcă doar dacă `POSTHOG_KEY` e setată, iar în `.env.example` e goală. Aplicația mobilă nu are niciun SDK de analytics |
| Dacă se activează | evenimente de pe landing (vizualizare, schimbarea limbii, începutul completării, trimiterea), un identificator anonim în `localStorage`, IP-ul, la PostHog (host în UE, `eu.i.posthog.com`) |
| Atenție | layout-ul e comun cu paginile `/@slug`, deci scriptul s-ar încărca și la cei care completează un link public, fără consimțământ. Condițiile de activare: `docs/21`, M-16 |
| În cod | `resources/views/components/layouts/landing.blade.php` |

### P10 · Securitate și operare

| Ce | Date | Retenție |
|---|---|---|
| Loguri de aplicație | erori și avertismente; conținutul notificărilor nu se loghează (`ExpoPushSender`). Excepțiile de bază de date pot include valori din cereri | producție: `LOG_STACK=daily`, 14 zile (`.env.example` e pentru dezvoltare și scrie într-un singur fișier) |
| Loguri Nginx | IP, URL, user agent | 14 zile, de configurat la deploy |
| Sesiuni web | IP, user agent, limba, tokenul CSRF; la o eroare de validare, câmpurile formularului, până la cererea următoare | 120 de minute; `SESSION_ENCRYPT=true` în producție |
| Limitarea ratei | chei derivate din IP, în cache | câteva minute |
| Cozi | `jobs`, `failed_jobs` — joburile eșuate păstrează excepția | `failed_jobs` fără termen — M-11 |
| Copii de siguranță | toate datele | 7 zilnice, 4 săptămânale, 3 lunare, criptate (`docs/10 § 2`) — **neconfigurate**, depind de VPS |
| Erori (Sentry) | — | **neinstalat** (S11.7); când se adaugă: scrubbing de PII, fără date despre persoane |

---

## 2. Împuterniciți și destinatari

| Furnizor | Rol | Ce primește | Unde | Stare |
|---|---|---|---|---|
| Găzduire (VPS, de ex. Hetzner) | împuternicit | toate datele | UE | neales; DPA la contractare |
| Expo (650 Industries, Inc.) | împuternicit — push | tokenul, titlul și textul notificării (nume + ocazie), datele de rutare | SUA | DPA-ul Expo de acceptat; mecanismul de transfer de documentat |
| Apple (APNs), Google (FCM) | livrarea notificărilor, la nivel de sistem | tokenul dispozitivului și conținutul notificării | SUA | termenii platformelor |
| Furnizor de email (SMTP) | împuternicit — rezumatul săptămânal | emailul, numele, numele persoanelor și ocaziile | de preferat UE | neales; DPA |
| Furnizor de AI | împuternicit — doar cu acordul utilizatorului | contextul pseudonimizat din P5 | depinde de furnizor | **inactiv**; condițiile în M-15 |
| PostHog | împuternicit — analytics pe site | P9 | UE | **inactiv**; DPA la activare |
| Sentry | împuternicit — erori | P10 | de ales regiunea UE | **neinstalat**; DPA la activare |
| Magazinele | operatori independenți | nimic de la noi; utilizatorul ajunge pe site-ul lor din link | — | — |
| Serverele de imagini ale magazinelor | — | IP-ul telefonului, când aplicația încarcă o poză de produs direct de la ei | — | de cântărit un proxy de imagini — M-23 |

---

## 3. Cum se exercită drepturile

| Drept | Utilizatorul | Persoanele adăugate de utilizator | Cine a completat un link |
|---|---|---|---|
| Informare | politica, în RO/RU/EN, din aplicație și de pe site | politica publică; nu îi putem contacta (P2) | textul de lângă bifă și linkul spre politică |
| Acces și portabilitate | export JSON din Cont (`ExportUserData`): persoanele, inclusiv cele șterse încă păstrate, ocaziile, clickurile, recomandările, dispozitivele, reminderele, acordul AI | cerere la operator — M-13 | cerere la operator |
| Rectificare | direct în aplicație | prin utilizator, sau persoana își trimite singură datele prin linkul lui | o completare nouă |
| Ștergere | din Cont, definitiv, fără să ne scrie | cerere la operator — M-13 | linkul de ștergere primit la final, fără cont |
| Opoziție și restricționare | cerere la operator | cerere la operator | retragerea completării |
| Retragerea consimțământului | AI: Cont → Inteligența artificială; rezumatul pe email: setări sau linkul de dezabonare | — | linkul de ștergere |
| Plângere | CNPDCP, menționat în politică | idem | idem |

---

## 4. Istoric

| Data | Ce s-a schimbat |
|---|---|
| 2026-09-13 | Prima versiune, scrisă după cod. Reparate pe loc, înainte de a fi trecute aici: text liber în contextul pentru AI; acordul AI fără cale de retragere; exportul incomplet; tokenul de push rămas activ după deconectare; formularele publice fără link spre politică; `WRITE_CONTACTS` și stocarea externă pe Android; politica fără furnizorul de email, fără conținutul notificărilor și fără copiile de siguranță |
