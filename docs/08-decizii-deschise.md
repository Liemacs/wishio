# 08 — Ce lipsește ca planul să fie complet

> Răspunsul direct la întrebarea „ce lipsește ca să pot începe lucrul”.
> Documentele 01–07 acoperă **ce** construim și **de ce**. Aici e ce **nu se poate decide fără tine** sau fără o informație din exterior.
> Împărțit în: 🔴 blocant (nu poți începe fără), 🟠 necesar înainte de cod, 🟡 necesar înainte de lansare.

---

## A. Decizii care depind numai de tine

| # | Decizie | Stare | Impact |
|---|---|---|---|
| A1 🔴 | **Cât timp ai pe săptămână?** 10h sau 35h schimbă complet planul (12 săpt. vs. 8 luni) | ? | tot |
| A2 🔴 | **Ești singur sau ai echipă?** Dacă singur: cine face design? RN+Laravel+AI+catalog+design de un singur om = risc real | ? | planificare |
| A3 🔴 | **Buget disponibil** pentru primele 6 luni (conturi dev, hosting, AI, domeniu, eventual design) | ? | fezabilitate |
| A4 🔴 | **Care e criteriul tău de abandon?** Dacă la 3 luni de la lansare ai 400 useri și 2% click rate — continui sau oprești? Scris acum, nu atunci | ? | sănătate mintală |
| A5 🟠 | **Numele final.** „Wishio” e verificat? Domeniu, App Store, marcă? | ? | brand, App Store |
| A6 🟠 | Faci **Faza 0** (validare 2 săpt. fără app) sau sari direct la MVP? | ? | 6 luni din viață |
| A7 🟠 | Companie sau persoană fizică? Conturile dev, contractele cu comercianții și factura pentru un magazin cer entitate juridică | ? | monetizare |

---

## B. Informații din exterior — nu le poți afla stând la birou

| # | De aflat | Cum | Blochează |
|---|---|---|---|
| B1 🔴 | **Câte contacte reale au ziua de naștere completată?** (riscul R1) | 10 telefoane de la prieteni, numărat manual, 1 oră | designul întregului onboarding |
| B2 🔴 | **Magaziner vrea parteneriat?** Ce dau: feed? statistici? revenue share? | email + apel, săptămâna asta | strategia de catalog |
| B3 🔴 | **Plătește vreun comerciant?** Cât? Pentru ce model? (riscul R5) | 5 magazine + 2 restaurante, discuții de 20 min | existența modelului de business |
| B4 🟠 | Magazinele mari (Darwin, Bomba, Ultra, Enter) au feed XML accesibil? | verificare tehnică, 2 ore | planul B de catalog |
| B5 🟠 | Există vreo rețea de afiliere funcțională în MD? | întrebat comercianții la B3 | monetizare |
| B6 🟡 | Care e realitatea plăților online în MD pentru group gifting? (Stripe nu e oficial disponibil; maib Mastercard Gateway și paynet sunt opțiunile locale) | discuție cu banca | v2 |
| B7 🟡 | Costul real de achiziție prin comunități (grupuri FB, Telegram, influenceri MD) | test cu 200 MDL | GTM |

---

## C. Artefacte care nu există încă

| # | Artefact | De ce e blocant | Efort |
|---|---|---|---|
| C1 🔴 | **Tabelul de onomastici + aliasuri de prenume** (RO + variante RU) | fără el, cold-start-ul rămâne nerezolvat — e cel mai valoros activ al MVP-ului | 2–3 zile |
| C2 🔴 | **Taxonomia de interese** (60–80 leaf-uri, RO/RU/EN, mapate pe categorii) | AI-ul nu poate funcționa fără ea; e contractul dintre LLM și catalog | 1–2 zile |
| C3 🔴 | **Catalogul seed** — 300–500 produse curate, cu preț, link, categorie, `gift_score`, traduceri | fără catalog nu există produs, doar calendar | 3–4 zile |
| C4 🟠 | **Inventarul de ecrane + wireframe-uri** (~25 ecrane) | fără el, estimările sunt ficțiune | 2 zile |
| C5 🟠 | **Design system** (culori, tipografie, carduri, stări goale, stări de eroare, loading) | RN fără design system → refactor garantat | 3–5 zile |
| C6 🟠 | **Fișierele de traducere `ro.json` / `ru.json` / `en.json`** cu tot copy-ul, nu doar etichete | copy-ul *este* produsul într-o aplicație de remindere | continuu |
| C7 🟠 | **Contractul API (OpenAPI 3.1)** | mobile și backend se pot dezvolta în paralel doar cu el | 1 zi |
| C8 🟠 | **Prompturile + JSON Schema + setul de evaluare** (30 profiluri de test) | fără eval, orice modificare de prompt e pe ghicite | 2 zile |
| C9 🟠 | **Schema de evenimente analytics** implementată din prima zi | vezi `docs/07-metrici.md` | 0,5 zile |
| C10 🟡 | Calendarul de sărbători cu relevanță pentru cadouri, MD + reguli de dată | ocazii recurente | 0,5 zile |
| C11 🟡 | **Privacy Policy + ToS în RO/RU/EN** | fără ele nu se poate submite | 1 zi + jurist |
| C12 🟡 | **DPIA + registrul prelucrărilor** (Legea 195/2024) | obligație legală, în vigoare din 23 aug 2026 | 1 zi |
| C13 🟡 | Materiale App Store / Play în 3 limbi (icon, screenshot-uri, descriere) | submit | 2 zile |
| C14 🟡 | 30–60 experiențe/locații Chișinău, descrise în 3 limbi | v1.1 | 2 zile |

---

## D. Conturi, acces și administrativ

| # | Ce | Cost | Termen |
|---|---|---|---|
| D1 🟠 | Apple Developer Program | 99 USD/an | **acum** — activarea poate dura |
| D2 🟠 | Google Play Developer | 25 USD o dată | acum |
| D3 🟠 | Domeniu (`.md` + `.com`/`.app`) | ~50–100 USD/an | acum, odată cu A5 |
| D4 🟠 | Hosting (VPS UE, ex. Hetzner) | ~15–40 EUR/lună | la sprintul 1 |
| D5 🟠 | Cont AI provider + limite de cheltuială | pay-as-you-go | sprintul 6 |
| D6 🟡 | PostHog, Sentry, Expo EAS | free tier la început | pe parcurs |
| D7 🟡 | Entitate juridică + cont bancar (pentru facturat comercianți) | variabil | înainte de primul venit |
| D8 🟡 | Storage pentru imagini (S3-compatibil) | mic | sprintul 5 |

---

## E. Lacune de conținut din planul inițial, acum acoperite

Ca să fie clar ce s-a adăugat față de conversația de pornire:

| Lipsea | Unde e acum |
|---|---|
| Strategie de cold-start (contactele nu au zile de naștere) | `02 § R1`, `04 § Name-day resolver` |
| Analiză legală (Legea 195/2024, App Store 5.1.2, AI disclosure) | `06` integral |
| Redesign al modelului „shared Person data”, care ar fi blocat publicarea | `04 § 2` |
| Onomastici ca motor de ocazii și de cold-start | `01 § Reframe 1`, `04 § 4` |
| Linkul personal ca buclă de creștere, nu ca feature secundar | `01 § Reframe 2`, `03` |
| Taxonomia de interese (contractul AI ↔ catalog) | `04 § 5` |
| i18n RO/RU/EN ca cerință de arhitectură | `05 § 6` |
| Ranking determinist + eval set (nu „AI face recomandări”) | `05 § 3` |
| Plan B și C pentru catalog, dacă Magaziner spune nu | `02 § R4`, `05 § 4` |
| Modele de monetizare care funcționează fără infrastructură de afiliere | `02 § R5` |
| Faza 0 de validare înainte de 7 sprinturi | `03` |
| Porți de abandon (kill criteria) | `02 § 3` |
| Dimensionarea pieței și necesitatea extinderii în RO | `02 § 1` |
| Schema de evenimente înainte de cod | `07` |

---

## F. Ordinea recomandată în următoarele 14 zile

```
Ziua 1     A1–A4 (răspunde-ți în scris), A5 nume + domeniu
Ziua 1     D1, D2 (conturile durează — pornește-le azi)
Ziua 2     B1  ← testul contactelor. O oră. Poate schimba tot designul.
Ziua 2     B2  ← email către Magaziner
Ziua 3–5   Faza 0: landing RO/RU/EN + formular + analytics
Ziua 5–7   B3  ← discuții cu comercianții (paralel cu traficul pe landing)
Ziua 6–12  Concierge: 20 de recomandări manuale. Notează tot.
Ziua 8–12  C1 tabelul de onomastici, C2 taxonomia (se pot face în serile astea)
Ziua 13    Evaluează G0, G1, G2
Ziua 14    DECIZIE: mergi la MVP, pivotezi, sau oprești
```

**Dacă treci porțile G0–G2, `PLAN.md` conține planul de 12 săptămâni pentru MVP.**
