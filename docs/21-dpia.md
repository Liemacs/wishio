# 21 — Evaluarea impactului asupra protecției datelor (DPIA)

> **Ciornă de lucru, 13 septembrie 2026.** Cerută de Legea nr. 195/2024 (echivalentul art. 35 din GDPR). Se aprobă de operator după A7, cu un jurist, înainte de lansare, și se revizuiește la orice schimbare din § 8. Activitățile, datele și destinatarii sunt în `docs/20`; aici stau riscurile și măsurile.

---

## 1. Ce evaluăm

Wishio ține pentru un utilizator lista oamenilor apropiați, cu ziua de naștere, onomastica și ce le place, îi amintește de ocazii și îi sugerează cadouri din magazine reale. Datele despre acești oameni le introduce utilizatorul: manual, din agenda telefonului sau prin linkul public, pe care oamenii îl completează singuri. Opțional, un context fără nume poate pleca la un furnizor de AI, ca sugestiile să vină cu explicații.

Evaluarea acoperă aplicația mobilă, API-ul, paginile publice (`/@slug`, landing-ul) și operarea. Starea evaluată: codul din 13 septembrie 2026, înainte de primul deploy.

---

## 2. De ce e necesară

Ghidurile WP248, preluate de EDPB, cer o DPIA când se întrunesc cel puțin două criterii. Aici se întrunesc cinci:

| Criteriu | La noi |
|---|---|
| Persoane vizate vulnerabile | utilizatorii își pot adăuga copiii; vârsta ajunge în context ca interval („sub 18”) |
| Persoane care nu sunt informate și nu își pot exercita ușor drepturile | oamenii adăugați de utilizator nu știu că apar în aplicație, iar noi nu avem cum să-i contactăm |
| Combinarea surselor | agenda, introducerea manuală, deducerea onomasticii și completările din linkul public ajung în aceeași fișă |
| Tehnologie nouă | AI generativ pentru explicații — deocamdată inactiv |
| Evaluare și profilare | interesele unei persoane sunt folosite ca să-i fie alese produse |

Scara e mică la lansare. Asta nu scoate obligația, doar reduce gravitatea unor riscuri.

---

## 3. Necesitate și proporționalitate

| Principiu | Cum îl respectăm | Dovada |
|---|---|---|
| Minimizare | din agendă se citesc doar numele și ziua de naștere; la server ajung doar contactele bifate; fără telefoane, emailuri, adrese, poze | `deviceContacts.ts`; `ContactImportTest` „nu stocheaza numere de telefon” |
| Limitarea scopului | datele unei persoane servesc doar utilizatorului care le-a introdus; nu se folosesc pentru publicitate și nu se vând | D-004, D-021 |
| Fără partajare între utilizatori | fără profiluri comune, fără potrivire între conturi după nume sau număr | testele „nu lasa un utilizator…” din `ContactImportTest`, `RecommendationsTest`, `SubmissionIdentityTest` |
| Pseudonimizare | contextul pentru AI: intervale, coduri, titluri din catalog; fără nume și fără text liber | `PersonContext`; `RecommendationsTest` „trimite spre AI doar date pseudonimizate…” |
| Securitate | note criptate la rest; parole și tokenuri ca hash; IP-urile formularelor publice ca HMAC | `PersonTrustTest` „cripteaza notele la rest”; `PublicProfileTest` „versioneaza consimtamantul si nu stocheaza IP-ul in clar” |
| Acuratețe | fiecare câmp are sursă și încredere; editarea manuală bate agenda; onomasticile deduse cer confirmare | `PersonTrustTest`, `ContactImportTest` |
| Limitarea stocării | ștergerea contului e reală; completările se retrag fără cont. **Lacune**: persoanele șterse logic, clickurile, cererile din Faza 0 — M-07…M-11 | `AccountComplianceTest` „sterge contul definitiv…” |
| Drepturi | export complet și ștergere din aplicație; acordul AI se retrage cu un comutator | `AccountComplianceTest` |
| Funcționare fără permisiuni | aplicația merge fără Contacts și fără push | S11.8 |

---

## 4. Interesul legitim pentru datele persoanelor adăugate

Temeiul pentru P2 din `docs/20`, verificat în trei pași.

**1. Scopul e legitim.** Utilizatorul vrea să nu uite ocaziile oamenilor apropiați și să le facă un cadou potrivit; operatorul oferă acest serviciu. Pentru utilizator e o activitate personală, aceeași pe care o face cu un calendar sau o agendă. Wishio însă prelucrează datele ca serviciu, deci excepția pentru activitățile strict personale nu îl acoperă pe el.

**2. Prelucrarea e necesară.** Un reminder de zi de naștere nu există fără nume și dată. Interesele și bugetul sunt minimul pentru o sugestie. Ce nu servește acestor două scopuri nu se colectează: telefoane, emailuri, adrese, poze.

**3. Echilibrul înclină spre utilizator, cu măsurile de mai jos.**
- *Așteptări rezonabile.* E o practică obișnuită să-ți notezi ziua de naștere a unui prieten. Oamenii se așteaptă să fie ținuți minte.
- *Natura datelor.* Nume, dată de naștere, gusturi — nu categorii speciale. Riscul real e textul liber, unde un utilizator poate scrie orice: notele sunt criptate și nu pleacă nicăieri, iar Termenii interzic datele sensibile despre alții.
- *Impactul asupra persoanei.* Nu i se ia nicio decizie, nu apare nicăieri public, nu e contactată. Cel mai rău scenariu realist e o breșă de securitate (R-03).
- *Controlul persoanei.* Poate cere ștergerea (M-13) și își poate corecta singură datele prin linkul public al utilizatorului.

**Concluzie:** interesul legitim poate fi invocat, cu măsurile M-07 și M-13 și cu interdicțiile din D-004 și D-021 păstrate. De confirmat de jurist, împreună cu excepția de la informare din art. 14 alin. (5) lit. b.

---

## 5. Riscuri

Probabilitatea și gravitatea: scăzută, medie, ridicată. Riscul rezidual e ce rămâne după măsurile existente; măsurile care îl coboară mai departe sunt în § 6.

| # | Risc | Afectați | Măsuri existente | Probabilitate | Gravitate | Rezidual |
|---|---|---|---|---|---|---|
| R-01 | Datele unei persoane ajung la alt utilizator („profiluri din umbră”) | persoanele adăugate | nimic partajat între conturi (D-004), nicio potrivire după nume (D-021), autorizare pe fiecare resursă, testată | scăzută | ridicată | **scăzut** |
| R-02 | Date sensibile în text liber: sănătate, relații, convingeri | persoanele adăugate | note criptate; niciun text liber spre AI; textul din „Spune-mi despre…” nu se stochează; Termenii interzic datele sensibile. **Necriptate**: „de evitat” scris liber (190 de caractere) și mesajul din completări (280) | medie | medie | **mediu** → M-22 |
| R-03 | Breșă de securitate pe server | toți | TLS; parole și tokenuri ca hash; note criptate; MySQL 8.4 în producție. **Lipsesc încă**: serverul, backup-ul criptat, 2FA pe conturi, Sentry | medie | ridicată | **mediu** până la deploy → M-14, M-17 |
| R-04 | Nume și ocazii trec prin SUA, în notificări | persoanele adăugate, cei care completează | conținut minim (nume + ocazie); nimic în loguri; politica o spune explicit | ridicată — e fluxul normal | scăzută | **scăzut** → M-14, M-21 |
| R-05 | Context despre persoane trimis unui furnizor de AI | persoanele adăugate | niciun furnizor activ; acord explicit, retractabil; context pseudonimizat, testat | scăzută | medie | **scăzut** → M-15 |
| R-06 | O completare ajunge în fișa altei persoane (omonimi) | cei care completează, persoanele adăugate | „Cine este?”: nicio lipire fără decizia proprietarului; retragerea nu șterge contactele existente (S9.7, S9.8) | scăzută | medie | **scăzut** |
| R-07 | Abuzul linkului public: spam, date false, cineva completează în numele altuia | proprietarul, persoana imitată | limitare pe IP; consimțământ; proprietarul decide; link de ștergere. Fără captcha | medie | scăzută | **scăzut** → M-18 |
| R-08 | Date despre minori; minori ca utilizatori | copiii | date minime; vârsta doar ca interval; nimic public. Termenii nu stabilesc o vârstă minimă | medie | medie | **mediu** → M-12 |
| R-09 | Păstrare peste necesar | toți | ștergerea contului e reală. Lacune: persoanele șterse logic, clickurile, cererile din Faza 0, completările fără răspuns, coada de notificări, joburile eșuate, tokenurile fără expirare | ridicată — e starea de azi | scăzută | **mediu** → M-07…M-11 |
| R-10 | Persoanele adăugate nu sunt informate | persoanele adăugate | politica publică; nu colectăm date de contact, deci nu le putem scrie | ridicată | scăzută | **acceptat**, de validat de jurist |
| R-11 | Cine nu e utilizator nu are o cale practică de a-și exercita drepturile | persoanele adăugate | linkul de ștergere, pentru cine a completat un link. Lipsesc adresa de contact și procedura | medie | medie | **mediu** → M-13 |
| R-12 | Analytics pe paginile publice, fără consimțământ | vizitatorii | inactiv: fără cheie, scriptul nu se încarcă | scăzută | scăzută | **scăzut** → M-16 |
| R-13 | Pagina publică a utilizatorului e văzută de oricine are linkul | utilizatorul | pagina e opțională; vizibilitate pe câmp și pe fiecare dorință; `noindex`; sufix aleator în slug; dezactivare | medie | scăzută | **scăzut** → M-20 |
| R-14 | Numele din notificare se vede pe ecranul blocat | persoanele adăugate | setările de previzualizare ale sistemului; tokenul se retrage la deconectare | medie | scăzută | **scăzut** → M-21 |
| R-15 | Pierderea datelor | toți | plan de backup, RPO și RTO (`docs/10`) — netestat | medie | medie | **mediu** → M-17 |
| R-16 | Serverele magazinelor văd IP-ul telefonului când se încarcă pozele produselor | utilizatorii | — | ridicată | scăzută | **scăzut** → M-23 |

Niciun risc nu rămâne ridicat.

---

## 6. Plan de măsuri

### Făcute în S11.3 — 13 septembrie 2026

| # | Măsură | Riscuri | Dovada |
|---|---|---|---|
| M-01 | Contextul pentru AI fără text liber: „de evitat” doar ca coduri, cadourile oferite doar ca titluri din catalog | R-02, R-05 | `RecommendationsTest` |
| M-02 | Acordul pentru AI: foaia și politica listează exact ce pleacă (vârsta, genul, relația, ocazia, bugetul, interesele, ce să evite, cadourile din catalog), iar acordul se retrage din Cont, cu un comutator. Până acum, foaia promitea retragerea „din setări”, dar comutatorul nu exista | R-05 | ecranul Cont; rămâne verificarea pe telefon |
| M-03 | Exportul complet: „de evitat”, persoanele șterse încă păstrate, ocaziile fără persoană, dispozitivele, reminderele, recomandările, clickurile, momentele acordului AI, detaliile completărilor | R-11 | `AccountComplianceTest` |
| M-04 | Link spre politică lângă bifa de consimțământ și în subsolul paginilor publice | R-10 | `PublicProfileTest` |
| M-05 | Politica, în RO/RU/EN: furnizorul de email, conținutul notificărilor, copiile de siguranță, clickurile, genul, ideile de cadou, completările primite, lista exactă pentru AI | R-04, R-10 | de revizuit de jurist |
| M-06 | Permisiuni și identificatori minimi: Android fără `WRITE_CONTACTS` și fără stocare externă; textul permisiunii de Contacts spune exact ce citim; tokenul de push se retrage la deconectare — până acum, un telefon deconectat primea în continuare reminderele, cu numele persoanelor | R-14 | `app.json`; rămâne verificarea pe telefon |

### Deschise

| # | Măsură | Riscuri | Termen |
|---|---|---|---|
| M-07 | Termen pentru persoanele șterse logic. Propunere: ștergere definitivă după 30 de zile (`Prunable` + `model:prune` zilnic); un reimport după termen creează o persoană nouă, fără note. Decizie de produs | R-09 | înainte de lansare |
| M-08 | Agregarea clickurilor mai vechi de 24 de luni: un tabel lunar pe comerciant și ofertă, apoi ștergerea rândurilor individuale | R-09 | obligatoriu până în septembrie 2028; recomandat înainte de lansare, ca să nu fie uitat |
| M-09 | Cererile din Faza 0: un termen (propunere: 6 luni după încheierea Fazei 0) și ștergerea la cerere în `wishio:requests` | R-09 | înainte de a porni Faza 0 public |
| M-10 | Completările fără răspuns expiră (propunere: 90 de zile), cu ștergerea datelor trimise | R-09 | înainte de lansare |
| M-11 | Curățare: coada de notificări mai veche de 13 luni; `failed_jobs` după 7 zile (`queue:prune-failed`); expirarea tokenurilor de acces nefolosite | R-09 | înainte de lansare |
| M-12 | Vârsta minimă în Termeni — vârsta consimțământului digital din Legea 195/2024, de confirmat de jurist — și ratingul de vârstă din store | R-08 | înainte de submit |
| M-13 | O adresă de contact pentru protecția datelor și procedura pentru cererile celor care nu sunt utilizatori: căutare după numele și data nașterii comunicate de solicitant, ștergerea potrivirilor exacte, fără să-i spunem utilizatorului cine a cerut | R-11 | după A7, înainte de lansare |
| M-14 | Contracte și infrastructură: DPA cu găzduirea în UE, cu Expo (inclusiv mecanismul de transfer) și cu furnizorul de email; TLS; backup criptat; 2FA pe conturile Apple, Google, hosting și domeniu; `LOG_STACK=daily`, `SESSION_ENCRYPT=true`, loguri Nginx la 14 zile | R-03, R-04 | la deploy |
| M-15 | **Condiții pentru activarea unui furnizor de AI** (blochează `WISHIO_AI_PROVIDER`): DPA; fără antrenare pe datele noastre; retenție zero sau minimă, scrisă în contract; regiunea și mecanismul de transfer; versiunea textului de acord stocată lângă `ai_consent_at`; etichetele din `docs/22` reverificate | R-05 | înainte de activare |
| M-16 | **Condiții pentru PostHog pe site** (blochează `POSTHOG_KEY`): consimțământ sau persistență doar în memorie; scriptul exclus de pe paginile `/@slug`; DPA | R-12 | înainte de activare |
| M-17 | Sentry cu scrubbing de PII și un test real de restaurare a backup-ului (S11.7) | R-03, R-15 | înainte de lansare |
| M-18 | Captcha pe formularul public, la primul abuz observat | R-07 | la nevoie |
| M-19 | Coloanele nefolosite (`people.contact_hash`, `people.avatar_path`, `people.claimed_user_id`, `users.phone_hash`) scoase printr-o migrare nouă sau documentate ca rezervate, ca schema să nu sugereze o colectare care nu există | — | recomandat |
| M-20 | ✅ `is_indexable` e respectată din S12.5: un profil public se indexează doar dacă proprietarul alege | R-13 | făcut |
| M-21 | Setare opțională pentru notificări fără nume („O zi de naștere mâine”) | R-04, R-14 | după feedback |
| M-22 | Criptare la rest pentru `person_avoids.free_text` și `profile_submissions.message` | R-02 | recomandat |
| M-23 | Pozele produselor servite prin serverul nostru (proxy cu cache), ca magazinele să nu vadă IP-ul utilizatorilor | R-16 | de cântărit la v1.1 |

---

## 7. Responsabilul cu protecția datelor și consultarea prealabilă

**DPO.** Art. 37 îl cere când activitatea de bază presupune monitorizarea regulată și sistematică a persoanelor pe scară largă sau prelucrarea pe scară largă a categoriilor speciale de date. Wishio nu urmărește comportamentul persoanelor adăugate și nu are categoriile speciale ca obiect al activității. La lansare, un DPO nu pare obligatoriu. Se reevaluează la extinderea în România și la fiecare revizuire a acestui document.

**Consultarea prealabilă a CNPDCP** (echivalentul art. 36) e necesară doar dacă un risc rezidual rămâne ridicat. După măsurile existente, niciunul nu e ridicat, iar cu M-07…M-17 făcute, toate coboară la scăzut. Nu e necesară, cu condiția ca măsurile marcate „înainte de lansare” să fie gata.

---

## 8. Când se revizuiește

- la activarea unui furnizor de AI (M-15) sau a analytics-ului (M-16);
- la orice dată nouă despre persoane: numere de telefon (dacă D-017 se schimbă), poze, orice legătură între conturi;
- la extinderea în România: GDPR direct aplicabil, reprezentant în UE;
- după o breșă de securitate;
- altfel, o dată pe an.

---

## 9. Aprobare

| Rol | Nume | Data |
|---|---|---|
| Operator | după A7 | |
| Jurist | | |
