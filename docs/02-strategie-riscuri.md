# 02 — Strategie, dimensiunea pieței și registrul de riscuri

> Acest document conține partea neplăcută. Citește-l înainte de a scrie prima linie de cod.

---

## 1. Matematica pieței — Moldova nu poate fi piața finală

| | |
|---|---|
| Populație RM (fără Transnistria) | ~2,4 mil. |
| Utilizatori smartphone | ~1,5 mil. |
| Segment 22–40, urban, cu obicei de cumpărare online | ~300–400k |
| Penetrare optimistă la 2 ani | 5–8% → **20–30k utilizatori activi** |
| Ocazii acționate / user / an (cu Occasion Engine) | 4–6 |
| Rată click spre magazin | 20–30% |
| Valoare medie per click monetizabil (CPC/afiliere MD) | **0,1–0,5 USD** (necunoscut — vezi R5) |
| **Venit anual realist din afiliere la 25k useri** | **~10–35k USD/an** |

**Concluzie brutală: Moldova nu e un business. Este un laborator de validare cu costuri mici și un avantaj de catalog local.**

Ce face totuși strategia validă:
1. Costul de a ajunge la primii 5.000 de utilizatori în Moldova este aproape zero (viral + comunități + PR local — piață mică, presă ieftină).
2. Dovada că *„help me choose” → click spre magazin* funcționează este transferabilă în orice piață.
3. Următoarea piață e evidentă: **România** — 19 mil., aceeași limbă, aceleași onomastici, aceleași sărbători, și **infrastructură de afiliere matură** (2Performant, Profitshare, eMAG Affiliate) care rezolvă exact problema de monetizare inexistentă în MD.

### Consecință arhitecturală obligatorie
Totul se construiește **multi-country de la început**, chiar dacă lansăm într-o singură țară:
- `country_code` pe user, pe merchant, pe produs, pe experiență, pe ocazie
- catalogul intră prin **adaptoare** (`CatalogAdapter` interface), nu printr-o integrare hardcodată cu Magaziner
- valuta, formatul de telefon, calendarul de sărbători și calendarul de onomastici — toate configurabile per țară
- traducerile: RO / RU la MVP, structură i18n care acceptă EN fără refactor

Costul acestei decizii acum: ~3 zile. Costul ei peste un an: ~2 luni.

---

## 2. Registrul de riscuri

Ordonat după *cât de repede poate omorî proiectul*.

---

### R1 — Cold-start: contactele NU au zile de naștere 🔴 CRITIC

**Ipoteza din planul inițial:** „Found 18 birthdays”.
**Realitatea:** câmpul `birthday` din agendă este completat pentru un procent foarte mic din contacte, în special pe Android. Un user cu 200 de contacte poate găsi 3–10 zile de naștere. Ecranul de onboarding arată gol → dezinstalare în prima sesiune.

**Acesta este riscul numărul unu al produsului și trebuie testat înainte de orice altceva.**

**Mitigări (toate, nu una):**
1. **Onomastica derivată din prenume** — nu necesită date. Un tabel `prenume → sfânt → dată` acoperă instant 60–80% din contactele cu nume românești. Transformă „am găsit 4 zile” în „am găsit 4 zile de naștere și 112 onomastici”.
2. **Google People API** (OAuth) — birthdays din Google Contacts sunt mult mai des completate decât cele de pe device, și merge pe ambele platforme.
3. **Link personal** (vezi Reframe 2) — sursa cea mai bună de date.
4. **Adăugare manuală ultra-rapidă** — un ecran cu listă de contacte și un date-picker, 3 secunde per persoană.
5. **Import din calendar** (Google/Apple Calendar au deseori evenimente recurente „Ziua lui X”).

**Test de validat în Faza 0:** ia 10 telefoane reale (prieteni, colegi) și numără efectiv câte contacte au `birthday`. Dacă media e sub 5%, importul din Contacts **nu poate fi** flow-ul principal de onboarding și produsul se construiește în jurul onomasticii + linkului.

---

### R2 — Modelul „shared Person data” încalcă legea și regulile Apple 🔴 CRITIC / KILL RISK

Planul din conversație propune: dacă mai mulți useri au același număr în agendă, backend-ul creează o entitate `Person` comună și **completează reciproc** ziua de naștere și poza între utilizatori.

**Acesta este, tehnic, un sistem de shadow profiles.** Se creează și se îmbogățește un profil despre o persoană care nu e utilizator, nu știe că există și nu a consimțit.

**Situația juridică, la zi:**
- **Legea nr. 195/2024** privind protecția datelor cu caracter personal este **în vigoare din 23 august 2026** și transpune integral GDPR (Regulamentul UE 2016/679). Înlocuiește Legea 133/2011, care era mult mai permisivă. Introduce răspundere strictă (operatorul trebuie să *demonstreze* conformitatea) și amenzi de până la ~2 milioane MDL.
- Nu există temei legal solid pentru a construi profiluri despre non-utilizatori din agendele altor persoane. Consimțământul nu îl ai. Interesul legitim nu trece testul de echilibrare când persoana vizată nu poate anticipa prelucrarea.
- **App Store Review Guideline 5.1.2** interzice explicit aplicațiilor să colecteze/compileze informații personale despre terți (contacte, prieteni) fără cunoștința și consimțământul acestora. Este un motiv frecvent de respingere.
- **Nou, din 13 noiembrie 2025:** Apple cere dezvăluire clară și permisiune explicită înainte ca date personale să fie trimise unui **AI terț** (OpenAI, Anthropic etc.). Recomandările noastre fac exact asta.
- Google Play are politici echivalente pentru User Data / Contacts.

**Redesign obligatoriu — vezi `docs/04-model-domeniu.md`. Pe scurt:**

| Interzis | În loc de asta |
|---|---|
| Upload de agendă brută pe server | Doar **hash-uri HMAC** ale numerelor normalizate, doar pentru contactele **selectate explicit** de user |
| `Person` global îmbogățit din agendele altor useri | `Person` este **scoped pe user**. Există doar un **bucket de identitate** (hash) care leagă tehnic |
| Ziua de naștere a lui Ana copiată de la User B la User A | Ziua de naștere devine „shared” **numai când Ana însăși o confirmă** (devine user, sau completează link-ul) |
| Poza de contact partajată între useri | Niciodată. Poza de contact rămâne pe device |

**Ce pierdem:** enrichment-ul automat silențios.
**Ce păstrăm:** 90% din valoare — pentru că datele confirmate de persoana însăși sunt oricum superioare (vezi ierarhia de trust), și linkul personal generează exact acest consimțământ.
**Ce câștigăm:** aplicația trece de App Review, și nu riscăm o amendă care ne închide.

> Regula de aur pe care o punem în CLAUDE.md: **nicio dată despre o persoană nu devine vizibilă altui utilizator decât dacă persoana respectivă a consimțit explicit.**

---

### R3 — Push notifications refuzate = produsul moare 🟠 MARE

Reminder-ul este mecanismul de retenție. Rata de opt-in la push pe iOS este tipic 45–65%, și scade dacă ceri permisiunea prea devreme.

**Mitigări:**
- **Nu cere push în primul ecran.** Cere-l după primul „aha moment” — după ce userul vede lista cu ocazii găsite: *„Vrei să te anunțăm cu 7 zile înainte?”* → abia atunci prompt-ul nativ.
- Fallback: **email digest săptămânal** + **notificări locale** (nu cer server, cer tot permisiune dar sunt mai ieftine) + **widget iOS/Android** cu următoarea ocazie (retenție fără push).
- Widget-ul e subestimat: e singurul canal care nu poate fi dezactivat prin refuzul push-ului.

---

### R4 — Dependența de Magaziner ✅ ÎNCHIS (era 🟠 MARE)

**Rezolvat.** Proprietarul Magaziner a acceptat să ofere un API cu toate produsele — vezi `docs/00 § D-008`. Magaziner agregă ~70.000 de produse din 15+ magazine (Ultra.md, Bomba, Darwin, Foxmart, Enter etc.), cu prețuri actualizate și statistici de clickuri.

**Riscul rezidual, mult mai mic:** dependență de un singur furnizor, pe o înțelegere verbală. Se acoperă prin (a) formalizarea în scris, o pagină — `docs/12`, și (b) păstrarea abstracției `CatalogAdapter`, ca sursa să fie înlocuibilă fără să atingi Recommendation Engine.

**Atenție — riscul s-a mutat, nu a dispărut:** problema nu mai e „nu am catalog", ci „am 70.000 de produse needitate". Vezi R7, care devine acum riscul tehnic principal.

**Mitigări:**
1. **Discuție cu ei în săptămâna 1**, nu în sprintul 5. Propunerea de valoare: le aducem trafic sezonier cu intenție de cumpărare, ei ne dau feed + revenue share. Rezultatul acestei discuții schimbă planul.
2. **Plan B — feed-uri directe.** Magazinele care alimentează Magaziner publică deja feed-uri Google Merchant (XML). Multe sunt accesibile direct. Se negociază cu 5–8 magazine mari individual. Mai multă muncă, dar control total și comision direct.
3. **Plan C — catalog curat manual pentru MVP.** 300–500 de produse „bune de cadou”, pe 10 categorii, cu preț și link. Se face în 2 zile. Suficient pentru a valida dacă oamenii dau click. **Pentru MVP, acesta e planul recomandat** — nu blochează lansarea pe o negociere.
4. Arhitectura: `CatalogAdapter` cu implementări `ManualCatalog`, `GoogleFeedCatalog`, `MagazinerCatalog`. Sursa se schimbă fără să atingi Recommendation Engine.

---

### R5 — Afilierea poate să nu existe ca infrastructură în Moldova 🟠 MARE

În România ai 2Performant / Profitshare cu tracking, cookie, comision automat. **În Moldova, infrastructura de afiliere este subțire sau inexistentă.** Un magazin local probabil nu poate să-ți atribuie o vânzare.

**Consecință:** modelul „comision din vânzări” poate fi neimplementabil la lansare.

**Modele de rezervă, în ordinea fezabilității:**
1. **Abonament lunar fix per magazin** pentru prezență + poziționare („Featured gift”, marcat clar ca sponsorizat). Simplu, vandabil, nu necesită tracking.
2. **CPC facturat manual**, cu dashboard de clickuri ca dovadă. Noi avem datele; ei plătesc lunar.
3. **Pachete sezoniere**: „Campania 8 Martie — 3.000 MDL, ești în top 3 la categoria Beauty timp de 2 săptămâni”.
4. **Restaurante/activități — rezervare cu comision fix**, negociat per partener (mai ușor decât retail).
5. Afiliere reală → doar în România, faza 2.

**De validat în Faza 0:** vorbește cu 3 magazine și 2 restaurante. Întrebarea: *„ai plăti 2.000 MDL/lună pentru 500 de clickuri de la oameni care caută cadou cu buget declarat?”*. Răspunsul lor decide dacă există business.

---

### R6 — Sezonalitate 🟡 MEDIU (mitigat)

Riscul care a omorât ideea Secret Santa. Mitigat prin Occasion Engine (§ Reframe 1). Cu onomastici + 8 Martie + Crăciun + Paște + zile de naștere, distribuția anuală devine relativ uniformă, cu vârfuri în martie și decembrie. **De verificat în date după primul an.**

---

### R7 — Calitatea recomandărilor pe un catalog mare 🟠 MARE *(promovat după D-008)*

Dacă la „cadou pentru Alex, 27 ani, mașini + gaming, 1000 MDL” aplicația scoate 3 produse irelevante, userul nu mai revine niciodată. Prima impresie e singura.

Cu API-ul Magaziner ai ~70.000 de produse. Marea majoritate **nu sunt cadouri**: cabluri, filtre de apă, consumabile, piese de schimb. Dacă intră toate în recomandări, produsul pare prost chiar dacă motorul e corect.

**Mitigări:**
- **Stratul de curatare este acum componenta critică a produsului.** Vezi `docs/05 § 4`. Nu catalogul te diferențiază — Magaziner îl are deja — ci faptul că știi *care dintre cele 70.000 e un cadou pentru Alex*.
- Ranking determinist (filtre + embeddings), LLM doar pentru extragerea criteriilor și pentru text explicativ. Vezi `docs/05-arhitectura.md`.
- **Set de evaluare** de 30 de persoane-fictive cu răspuns așteptat, rulat la fiecare schimbare de prompt. Fără asta, zbori pe ceață.
- Fallback onest: *„Nu avem destule idei bune pentru acest buget — încearcă X sau mărește bugetul”* e mai bun decât 3 sugestii proaste.

---

### R8 — Capacitatea fondatorului 🟡 MEDIU

Planul inițial listează 7 sprinturi + mobile + backend + AI + catalog + merchant dashboard. Pentru un dezvoltator, cu job, asta e 6–9 luni, nu 7 săptămâni.

**Mitigare:** Faza 0 (2 săptămâni, fără aplicație) înainte de orice. Vezi `docs/03-scop.md`. Reduce riscul de a construi 6 luni ceva ce nimeni nu vrea.

---

### R9 — „ChatGPT face asta gratis” 🟡 MEDIU

Aceeași obiecție care a îngropat ideea Stack Generator se aplică și aici. Un utilizator poate cere idei de cadouri oricărui asistent AI.

**Ce nu poate face ChatGPT și noi da:**
- nu știe că Ion are ziua în 5 zile și nu te anunță
- nu știe ce prețuri sunt azi la Darwin sau Bomba
- nu ține minte că anul trecut i-ai luat căști
- nu știe că sora ta a rezervat deja ceasul
- nu îți dă un link de cumpărare care funcționează

**Valoarea nu e generarea de idei. Este memoria + declanșatorul + catalogul real.** Orice feature care nu întărește unul din aceste trei lucruri e discutabil.

---

### R10 — Numele și brandul 🟢 MIC dar blocant

`Wishio` (numele folderului) nu e verificat. Trebuie: disponibilitate `.md` / `.com` / `.app`, nume disponibil pe App Store și Play Store, marcă verificată la AGEPI (MD) și EUIPO dacă mergem în UE. Vezi `docs/08-decizii-deschise.md`.

---

## 3. Porțile de validare (kill criteria)

Nu continua la faza următoare dacă nu treci poarta. Scris acum, ca să nu te minți mai târziu.

| Poartă | Când | Criteriu de trecere | Dacă pici |
|---|---|---|---|
| **G0 — Interes** | după 2 săpt. de Faza 0 | 150+ emailuri din trafic organic/comunități, la cost ≈0 | mesajul e greșit, nu produsul |
| **G1 — Valoarea „help me choose”** | Faza 0 | din 20 de recomandări date manual, 10+ persoane spun „asta chiar aș cumpăra”, 5+ dau click spre magazin | **oprește proiectul** sau pivotează pe pur wishlist |
| **G2 — Merchant plătește** | Faza 0 | 2 din 5 comercianți spun „da, aș plăti” cu o cifră concretă | nu există business, doar utilitate |
| **G3 — Onboarding** | 2 săpt. de la lansare MVP | >60% din useri ajung la 5+ persoane adăugate | cold-start-ul e nerezolvat (R1) |
| **G4 — Reminder funcționează** | 1 lună | >35% din push-uri deschise | mecanismul de retenție e mort |
| **G5 — Conversie spre acțiune** | 2 luni | >20% din cei care deschid un reminder cer recomandări; >15% din ei dau click spre magazin | produsul e doar calendar |
| **G6 — Retenție** | 3 luni | D30 > 25% | nu e un produs recurent |
| **G7 — Viralitate** | 3 luni | K > 0.25 | achiziția va trebui plătită — recalculează economia |
