# 13 — Recomandarea mea, punct cu punct

> Pentru fiecare element deschis din `docs/08`: ce aș face eu, concret, și de ce.
> Unde decizia e a ta, spun ce aș alege și cu ce argument — nu îți dau o listă de opțiuni.

---

## A. Deciziile tale

### A1 — Câte ore pe săptămână ✅ *(15 h, `docs/00 § D-010`)*
**Recomandare: planifică pe 15 ore, nu pe cât speri.**

Majoritatea proiectelor solo eșuează nu din lipsă de timp, ci din planificare pe timpul optimist. Dacă ai un job, 15 h/săptămână susținute bat 35 h în două săptămâni bune urmate de o lună de nimic.

Concret: **două seri fixe + o jumătate de zi în weekend.** Puse în calendar ca întâlniri. Cu 15 h/săptămână, MVP-ul din `PLAN.md` durează ~16 săptămâni, nu 12. Scrie 16 și respectă-le, în loc să scrii 12 și să ratezi.

### A2 — Singur sau cu echipă 🔴
**Recomandare: singur pe tot, cu o excepție — designul.**

Backend-ul e zona ta. RN + NativeWind se învață pe parcurs. Dar 31 de ecrane fără ochi de designer arată ca un proiect de facultate, iar produsul ăsta se vinde pe senzația de îngrijire — competiția directă e Apple Calendar și Airbnb, nu un CRM.

Concret: **un designer, 8–10 zile, în două tranșe** — S1.6 (design system: culori, tipografie, componente) și S9 (ecranele publice, care sunt fața produsului). În Moldova, 600–1.500 USD. Este cea mai bună cheltuială din tot bugetul.

Dacă nu ai bani deloc: cumpără un UI kit de 50–100 USD pentru mobile și **respectă-l strict**, fără improvizații.

### A3 — Buget 🔴
**Recomandare: 1.500 USD pentru primul an. Minim absolut 250.**

| | USD |
|---|---|
| Apple + Google + `wishio.md` + `wishio.ro` | ~160 |
| Hosting și servicii, 12 luni | ~250 |
| Design (A2) | 600–1.000 |
| Juridic — Privacy Policy, ToS revizuite | 200–400 |
| Micro-influenceri la lansare | 100–300 |

Faza 0 costă **zero**. Nu cheltui nimic până nu treci G1. Dacă G1 pică, ai pierdut două săptămâni, nu 1.500 USD.

### A5 — Nume și domeniu ✅
**Rezolvat: Wishio / wishio.md** — vezi `docs/00 § D-009`.

Rămâne: înregistrează și `wishio.ro` (~10 €, piața #2), verifică numele în App Store și Play, și verifică marca la AGEPI. 20 de minute, o singură dată.

### A6 — Faza 0 sau direct MVP 🟠
**Recomandare: Faza 0, fără excepție.**

Știu că e tentant să sari — ai deja infrastructura, API-ul Magaziner vine, planul e scris. Dar Faza 0 nu testează dacă poți construi. Testează **dacă cineva vrea**, iar asta nu se află din cod.

Și mai important: cele 20 de recomandări manuale sunt **corpusul din care se scrie promptul AI**. Fără ele, la S7 vei inventa criterii din burtă. Cu ele, ai 20 de exemple reale de cum vorbesc oamenii despre cadouri, în română și rusă.

Faza 0 nu e o întârziere de două săptămâni. E input pentru S7 și S8.

### A7 — Persoană fizică sau companie 🟠
**Recomandare: PF acum, SRL înainte de prima factură.**

Contul Apple Developer ca persoană fizică se activează în zile. Ca firmă, cere număr D-U-N-S și poate dura săptămâni — nu are rost să blochezi lansarea pe asta.

**Atenție la o capcană:** transferul unei aplicații de la cont personal la cont de firmă este posibil, dar cu fricțiune (Apple cere ca ambele conturi să fie în regulă, procesul durează). Dacă ești destul de sigur că va exista SRL și ai timp, contul de firmă de la început e mai curat.

Sub Legea 195/2024 ai nevoie oricum de un operator de date identificabil. Pentru Faza 0 și MVP, PF e acceptabil. Înainte să facturezi primul magazin, ai nevoie de SRL.

---

## B. Faptele din exterior

### B1 — Câte contacte au ziua de naștere 🔴
**Recomandare: fă-o în prima oră de lucru, pe telefonul tău.**

Nu ai nevoie de 10 telefoane pentru primul semnal. Deschide Contacte pe telefonul tău, numără câte au ziua completată, împarte la total. Apoi întreabă 5 prieteni să facă la fel și să-ți trimită două cifre.

**Ce faci cu rezultatul:**

| Rezultat | Consecință |
|---|---|
| peste 15% | importul din Contacts poate fi flow-ul principal |
| 5–15% | onomasticile devin egale ca importanță cu zilele de naștere |
| **sub 5%** *(cel mai probabil)* | **onomasticile devin flow-ul principal**, iar ecranul O8 trebuie să vorbească despre ele întâi |

În toate trei cazurile construiești name-day resolver-ul. Cifra decide doar **ce scrie pe ecran**, și asta contează mai mult decât pare.

### B2 — Magaziner ✅
**Rezolvat.** Recomandarea acum: trimite-i `docs/12 § 2.1–2.2` și **acceptă varianta simplă**.

Dacă îi e mai ușor un export JSON zilnic pe un URL privat decât un API cu `updated_since`, ia exportul. Delta e o optimizare, nu o condiție de lansare. Nu-l pune să construiască ceva complicat pentru un partener care încă n-a dovedit că aduce trafic.

Cere însă **un set de test de 500 de produse acum** — îți deblochează maparea taxonomiei cu săptămâni înainte de API-ul complet.

### B3 — Plătește vreun comerciant 🔴
**Recomandare: cere-i lui introducerea.**

Ai acum ceva ce n-aveai acum o oră: un partener credibil în ecosistem. O introducere din partea lui valorează cât zece emailuri reci.

Întrebarea concretă, către comerciant: *„Dacă îți aduc 500 de clickuri pe lună de la oameni care caută cadou, cu buget declarat și dată fixă, cât ar valora asta pentru tine?"* Lasă-i pe ei să spună cifra. Dacă toți spun „nimic", ai aflat ieftin lucrul cel mai important.

### B4 — Feed-uri XML directe 🟡
**Recomandare: renunță.** Rezolvat de B2. Rămâne doar ca plan de rezervă, documentat, neimplementat.

### B5 — Infrastructură de afiliere în MD 🟠
**Recomandare: întreabă-l pe proprietarul Magaziner.** El știe răspunsul mai bine decât orice căutare. Este a doua din cele trei întrebări din `docs/12 § 1`.

### B6 — Plăți online 🟡
**Recomandare: amână complet.** E o problemă de v2, și chiar și acolo prima versiune de group gifting se face **fără** colectare de bani — doar coordonare și „cine cât dă". Stripe nu e oficial disponibil în MD; alternativele sunt maib Mastercard Gateway și paynet. Nu cheltui gândire pe asta acum.

### B7 — Costul achiziției 🟡
**Recomandare: îl afli gratis în Faza 0.** Distribuția landing-ului îți dă exact acest număr.

---

## C. Artefactele

### C1 — Tabelul de onomastici 🔴
**Recomandare: îl construiesc eu, e următorul pas logic.**

Este cel mai bun raport efort/impact din tot MVP-ul și **nu depinde de nimic** — nici de Faza 0, nici de API, nici de design. Se poate face acum, în paralel cu validarea.

Domeniu: ~150 de prenume românești cu variante RU, diminutive și forme scurte (`Gheorghe / George / Gicu / Жора / Георгий`), mapate pe sfinți și date din calendarul ortodox. Plus regula de normalizare fără diacritice, deja validată pe MariaDB.

**Atenție la un detaliu:** calendarul ortodox pe stil vechi și nou diferă, iar unii sfinți au mai multe date. Tabelul are `calendar` ca dimensiune tocmai pentru asta. Datele trebuie verificate cu o sursă bisericească, nu generate din memorie — aici greșelile se văd direct în notificări.

### C2 — Taxonomia de interese 🔴
**Recomandare: v1 acum, mapare finală după setul de test de la Magaziner.**

Scrie cele ~70 de leaf-uri acum, în RO/RU/EN — structura nu depinde de catalog. Dar **maparea interes → categorie** se face pe categoriile lor reale, nu pe presupuneri. De aceea setul de test de 500 de produse contează.

### C3 — Catalogul 🔴 → *transformat*
**Recomandare: nu mai construiești un catalog. Construiești un filtru.**

Vezi `docs/05 § 4`. Ordinea:
1. reguli pentru `gift_score` pe toate cele 70.000 (1 zi)
2. corecție manuală pe **top 500–1.000 după popularitate** (2–3 zile)
3. învățare din comportament, după lansare

Pasul 2 e muncă plictisitoare și e cel mai bine plătit efort din proiect. Nu-l delega și nu-l sări.

### C4 — Ecrane ✅
**Rezolvat** — `docs/09`, 31 de ecrane. Rămân wireframe-urile vizuale, care intră în pachetul designerului (A2).

### C5 — Design system 🟠
**Recomandare: tokenii există deja** în `mobile/global.css` (paletă, forme). Ce lipsește: scara tipografică, spațierea și cele 8 componente de bază. Prima tranșă a designerului.

### C6 — Copy 🟠
**Recomandare: scrie-l tu, în RO și RU. Nu-l externaliza.**

Ești vorbitor nativ de ambele și cunoști contextul. ~150 de texte, dar nu deodată — fac parte din Definition of Done al fiecărui ecran. EN-ul îl scrii tu și îl dai unui vorbitor nativ la revizuit înainte de store, o singură dată.

### C7 — Contract API 🟠
**Recomandare: se scrie în S1, împreună cu primele endpointuri.** Un OpenAPI scris înainte de cod pe un produs nevalidat e ficțiune. Scris odată cu codul, e util.

### C8 — Prompturi și set de evaluare 🔴
**Recomandare: se naște din Faza 0. De aceea P0.8 cere să notezi tot.**

Cele 20 de cazuri reale devin primele 20 din cele 30 de profiluri de evaluare. Restul de 10 le inventezi pentru cazurile-limită: buget foarte mic, persoană fără interese, categorie de evitat.

### C10–C14 🟡
Sărbătorile (0,5 zile) și experiențele (2 zile) — la momentul lor. Juridicul și materialele de store — S11–S12. Nimic de decis acum.

---

## D. Administrativ

**Recomandare: fă azi D1, D2, D3.** Sunt 30 de minute și ~160 USD, iar activarea conturilor durează. Restul vine la momentul lui.

Ordinea în care aș deschide lucrurile în browser chiar acum:
1. `wishio.md` și `wishio.ro` la un registrar
2. Apple Developer Program
3. Google Play Console
4. Căutare „Wishio" în App Store și Google Play

---

## Ce aș face în ordinea asta, începând de mâine

| Când | Ce | De ce acum |
|---|---|---|
| **Ziua 1** | D1–D3: domenii + conturi dev | activarea durează, pornește-le |
| **Ziua 1** | B1: numără zilele de naștere din agenda ta | o oră, poate schimba ecranul O8 |
| **Ziua 1** | Trimite `docs/12` proprietarului Magaziner + cere setul de test | el are nevoie de timp, tu ai nevoie de date |
| **Zilele 2–4** | Landing RO/RU/EN + formular + analytics | Faza 0 începe |
| **Zilele 5–7** | Distribuție + primele cereri; B3 și B5 prin introducerea lui | paralel cu traficul |
| **Zilele 6–12** | 20 de recomandări manuale, notate integral | corpusul pentru S7–S8 |
| **Serile 8–12** | C1 onomastici + C2 taxonomia v1 | nu depind de nimic, se pot face în paralel |
| **Ziua 13** | Evaluează G0, G1, G2 | |
| **Ziua 14** | Decizia: MVP, pivot, sau stop | |

**Singurul lucru pe care l-aș începe înainte de a trece porțile** este C1 — tabelul de onomastici. E util indiferent de rezultat, nu depinde de nimic, și se poate face în serile din Faza 0.
