# 11 — Costuri, economia unitară și lansarea

> `docs/02` calculează piața. Aici e cealaltă jumătate: **cât costă** și **de unde vin primii utilizatori**.

---

## 1. Costuri unice

| | USD |
|---|---|
| Apple Developer Program (an 1) | 99 |
| Google Play Developer (o dată) | 25 |
| Domeniu `.md` + `.com` (an 1) | 60–120 |
| Marcă AGEPI (opțional, dar recomandat) | 150–300 |
| Consultanță juridică: Privacy Policy + ToS + DPIA | 200–500 |
| **Total pentru a putea lansa** | **~550–1.050** |

Dacă bugetul e strâns: Apple + Google + domeniu = **~200 USD** te duc până la lansare. Juridicul nu poate fi sărit, dar prima versiune poate fi scrisă de tine și revizuită de un jurist mai târziu — cu riscul asumat.

---

## 2. Costuri lunare

| | Dezvoltare | Lansare (~1k useri) | Creștere (~10k) | ~25k useri |
|---|---|---|---|---|
| VPS (Hetzner) | 5 | 15 | 30 | 60 |
| Object storage | 0 | 1 | 3 | 8 |
| Backup storage | 0 | 2 | 4 | 8 |
| AI (vezi §3) | 2 | 8 | 60 | 140 |
| PostHog | 0 | 0 | 0 | 20 |
| Sentry | 0 | 0 | 26 | 26 |
| EAS builds | 0 | 0 | 19 | 19 |
| Email transacțional | 0 | 0 | 10 | 20 |
| Domeniu + TLS | 5 | 5 | 5 | 5 |
| **Total USD/lună** | **~12** | **~31** | **~157** | **~306** |

**Concluzia care contează:** poți ține produsul în viață cu **sub 35 USD/lună** în primul an. Nu ai nevoie de finanțare ca să afli dacă ideea funcționează. Asta e principalul argument în favoarea acestui proiect.

---

## 3. Costul AI — modelat, nu ghicit

O rulare de recomandare = două apeluri scurte (criterii + explicații), ~2.500 tokeni intrare + ~600 ieșire.

| | |
|---|---|
| Cost per rulare | ~0,003–0,010 USD, în funcție de model |
| Rulări per utilizator activ pe lună | ~1,5 |
| **Cost AI la 10.000 de utilizatori activi** | **~45–150 USD/lună** |

**Trei pârghii de control, toate în arhitectură deja:**
1. **Cache pe `recommendation_runs`** — același profil + același buget în 7 zile → rezultat refolosit. Taie 30–40%.
2. **Explicațiile se generează lazy**, doar în limba cerută (`reason_ro/ru/en`). Fără asta, triplezi costul degeaba.
3. **Circuit breaker** la `WISHIO_AI_MAX_COST_PER_RUN` — dincolo de prag, rută doar-filtre, fără AI. Produsul degradează, nu cade.

Ranking-ul e determinist (`docs/05 § 3`), deci **costul nu crește cu mărimea catalogului**, doar cu numărul de cereri.

---

## 4. Economia unitară

| | Pesimist | Realist | Optimist |
|---|---|---|---|
| Ocazii acționate / user / an | 2 | 4 | 6 |
| Rată click spre magazin | 15% | 25% | 35% |
| Clickuri / user / an | 0,3 | 1,0 | 2,1 |
| Venit per click (MD) | 0,10 | 0,25 | 0,50 |
| **Venit / user / an (USD)** | **0,03** | **0,25** | **1,05** |
| **La 25.000 useri** | **750** | **6.250** | **26.250** |

**Citește asta cu onestitate:** chiar în scenariul optimist, Moldova singură dă ~26k USD/an. Acoperă costurile și puțin peste. **Nu e un salariu.**

De aceea strategia reală are două etaje:
1. **Moldova = laborator.** Dovedești ieftin că bucla funcționează.
2. **România = piața.** 19 milioane, aceeași limbă, aceleași onomastici, plus infrastructură de afiliere matură (2Performant, Profitshare, eMAG) care rezolvă exact problema din `docs/02 § R5`. Aceiași multiplicatori pe o piață de 8× mai mare, cu venit per click de 2–3× mai bun.

**Pragul de rentabilitate** (acoperi ~160 USD/lună la 10k useri): ~7.700 useri în scenariul realist. Atins plauzibil în anul 2, în Moldova. Mai devreme, cu venituri din sponsorizări (§5).

---

## 5. Monetizare — ordinea de implementare

Nu în ordinea profitabilității, ci în ordinea **fezabilității**. Vezi `docs/02 § R5` — afilierea cu tracking real poate să nu existe în Moldova.

| # | Model | Când | De ce în ordinea asta |
|---|---|---|---|
| 1 | **Abonament fix lunar per magazin** pentru prezență și poziționare | v2 | nu cere tracking, se vinde cu un dashboard de clickuri ca dovadă |
| 2 | **Pachete sezoniere** („Campania 8 Martie, top 3 la Beauty, 2 săptămâni") | v2 | bugete de marketing existente, decizie ușoară |
| 3 | **CPC facturat manual** | v2 | ai datele; ei plătesc lunar |
| 4 | **Comision fix pe rezervare** la restaurante/activități | v1.1 | mai ușor de negociat decât retail |
| 5 | **Afiliere reală cu tracking** | România | abia acolo există infrastructura |
| 6 | **Premium pentru utilizatori** | doar după ce există utilizare dovedită | nu ceri bani înainte să demonstrezi valoarea |

Poziția sponsorizată este a 5-a componentă a scorului (`config/wishio.php`), cu greutate 0,10 — suficient ca să conteze, prea puțin ca să strice relevanța. **Marcată vizibil ca sponsorizată, întotdeauna.**

---

## 6. Primii 1.000 de utilizatori

Moldova e o piață mică — asta e un avantaj: ajungi la ea cu efort, nu cu bani.

### Faza 0 — înainte de aplicație (150 de leaduri)
Landing RO/RU/EN + concierge manual. Canale: grupuri Facebook din Moldova, Telegram, colegi, cunoscuți. **Cost: 0.**

### Săptămâna lansării (0 → 300)
- Lista de emailuri din Faza 0 — sunt oameni care au primit deja ajutor real de la tine
- Cercul personal — cere instalare *și* partajarea linkului personal, nu doar instalare
- Grupuri Facebook MD (părinți, IT, cumpărături, mame), Telegram local
- Reddit r/moldova
- Un post onest: „am construit asta, uite de ce" — funcționează mai bine decât reclama

### Lunile 1–3 (300 → 1.000)
| Canal | Mecanism | Cost |
|---|---|---|
| **Bucla virală** | fiecare user partajează linkul; **acesta e canalul principal** | 0 |
| **SEO** | ghiduri „ce cadou pentru...", indexabile, RO/RU | timp |
| **PR local** | presa din MD scrie despre produse locale; unghi: „aplicație făcută în Moldova" | 0 |
| **Micro-influenceri** | 3–5 conturi locale, 5–20k urmăritori | 100–300 USD |
| **Sezonier** | vârfurile: 8 Martie, Crăciun, 1 Iunie | efort concentrat |
| **Parteneriat magazine** | ei au audiență, tu ai unealta | 0, doar negociere |

### Ce NU face la început
Reclamă plătită pe Facebook/Google. Într-o piață mică, cu un produs nevalidat, arzi bani înainte să știi dacă retenția există. **Plătește pentru achiziție abia după ce G6 (D30 > 25%) e trecut.**

---

## 7. Momentul lansării

Produsul are vârfuri sezoniere clare. Cel mai bun moment de lansare este **cu 4–6 săptămâni înainte de un vârf**, ca să ai timp de reparat ce se strică.

| Vârf | Pregătit de | Observație |
|---|---|---|
| **8 Martie** | mijlocul lui ianuarie | cel mai bun vârf pentru MD — cerere masivă, buget mic, decizie rapidă |
| **Crăciun / Anul Nou** | început de noiembrie | cel mai mare volum, dar cea mai mare concurență |
| **1 Iunie** | jumătatea lui aprilie | vârf secundar |
| **Sf. Maria, Sf. Nicolae** | 3 săptămâni înainte | **testul real al Occasion Engine** — dacă onomasticile aduc trafic, teza e validată |

Lansarea pe un vârf de onomastică este cel mai bun test posibil al ipotezei centrale a produsului.
