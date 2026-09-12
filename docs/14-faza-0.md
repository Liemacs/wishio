# 14 — Faza 0, în detaliu

> Răspuns la A6. Trei variante, cu ce câștigi și ce riști la fiecare, apoi planul zi cu zi.
> Ritm: **15 h/săptămână** (`docs/00 § D-010`).

---

## 1. Ce testează, de fapt

Nu testează dacă poți construi aplicația. Testează trei lucruri pe care codul nu ți le poate spune:

| Ipoteză | Cum o testezi | Dacă e falsă |
|---|---|---|
| **H1** — oamenii chiar nu știu ce cadou să ia și vor ajutor | cer ajutorul nesolicitat, la un landing fără brand | produsul rezolvă o problemă imaginară |
| **H2** — o recomandare bună îi face să dea click spre magazin | trimiți 5 produse reale și vezi ce fac | ai un calendar, nu un motor de cumpărare |
| **H3** — cineva plătește pentru traficul ăsta | întrebi 5–7 comercianți | ai utilitate, nu business |

Și produce un **al patrulea rezultat, care nu e un test**: corpusul de 20 de conversații reale din care se scriu prompturile la S7. Fără el, la S7 inventezi criterii din burtă.

> Cu API-ul Magaziner confirmat, ispita de a sări direct la cod e mai mare ca niciodată. Rezistă-i. API-ul rezolvă *de unde iei produse*. Faza 0 răspunde la *dacă cineva vrea să i le recomanzi*. Sunt întrebări diferite.

---

## 2. Trei variante

### V1 — Concierge manual *(pur)*

Landing simplu + căutare de mână pentru fiecare cerere.

| | |
|---|---|
| Efort | ~25 h → **2 săptămâni** la 15 h/săpt. |
| Cost | 0 |
| Cod scris | landing-ul, atât |
| **Câștigi** | semnalul cel mai curat; **înveți catalogul cu mâna** — ce categorii sunt inutile, ce benzi de preț contează, ce lipsește |
| **Riști** | fiecare recomandare ia 30–45 min de căutare; la 20 de cereri, ~12 h doar de căutat |

### V2 — Concierge instrumentat

La fel, dar îți construiești întâi o unealtă internă (Laravel, un singur ecran) care importă setul de test de la Magaziner și te lasă să filtrezi rapid după categorie, preț și cuvinte-cheie.

| | |
|---|---|
| Efort | ~40 h → **3 săptămâni** |
| Cost | 0 |
| Cod scris | landing + unealtă internă (~8–10 h) |
| **Câștigi** | fiecare recomandare scade la ~10 min; **testezi integrarea Magaziner cu 2 luni înainte de S6**; maparea taxonomiei iese ca efect secundar; codul se refolosește |
| **Riști** | **scope creep.** „Încă un filtru, încă un ecran" și te trezești construind produsul fără să fi validat nimic. Plus: unealta te izolează de catalog — nu mai vezi mizeria din el |

### V3 — Doar landing și interviuri

Landing + 10 interviuri de 20 de minute. Fără recomandări livrate.

| | |
|---|---|
| Efort | ~12 h → **1 săptămână** |
| Cost | 0 |
| **Câștigi** | rapiditate; testezi H1 și H3 |
| **Riști** | **nu testează H2**, care e ipoteza centrală. Oamenii spun în interviu că ar vrea ajutor; asta nu înseamnă că dau click. Și **nu produci corpusul** pentru S7 |

---

## 3. Recomandarea mea

**Începe cu V1. După 8 recomandări livrate, decide dacă construiești unealta.**

Argumentul: primele 8 căutări manuale sunt cea mai densă sursă de învățare din tot proiectul. Vei descoperi lucruri pe care nicio unealtă nu ți le arată — că jumătate din catalog sunt consumabile, că sub 300 MDL nu există nimic decent, că la „fitness" ies suplimente și nu echipament. **Exact astea devin regulile de `gift_score` din `docs/05 § 4`.**

Dacă după 8 simți că pierzi timpul căutând, atunci unealta e justificată de durere reală, nu de entuziasm. Iar la momentul acela vei ști exact ce filtre să-i pui — pentru că le-ai folosit cu mâna.

**Regula dacă ajungi la V2:** maximum 8 ore, un singur ecran, urât. Fără autentificare, fără design, fără ecrane suplimentare. Dacă depășești 8 ore, oprește-te și întoarce-te la căutat de mână.

---

## 4. Planul zi cu zi

### Săptămâna 1 (15 h)

#### Ziua 1 — Administrativ și decizii *(3 h)*
- [ ] Înregistrează **wishio.md** și **wishio.ro**
- [ ] Deschide **Apple Developer** și **Google Play Console** — activarea durează, pornește-le azi
- [ ] Caută „Wishio" în App Store și Google Play; verifică marca la AGEPI
- [ ] **B1:** deschide Contacte pe telefonul tău, numără câte au ziua completată. Notează procentul în `docs/00`.
- [ ] Trimite proprietarului Magaziner **`docs/12 § 2.1–2.2`** și cere **setul de test de 500 de produse**

#### Zilele 2–4 — Landing *(9 h)*

Un singur ecran, în RO/RU/EN. **Fără logo, fără brand elaborat** — testezi mesajul, nu identitatea vizuală.

**Conținutul, exact:**

> **Nu știi ce cadou să iei?**
> Scrie-ne despre persoană. Îți trimitem 5 idei reale, cu preț și magazin din Moldova.
> Gratis, în 24 de ore.

**Formularul — 7 câmpuri, nu mai multe:**

| Câmp | De ce e acolo |
|---|---|
| Pentru cine? (relația) | intră în criteriile AI |
| Vârsta aproximativă | idem |
| Ce îi place? *(text liber)* | **cel mai important câmp** — aici vezi cum vorbesc oamenii despre oameni |
| Bugetul | validează benzile de preț |
| Ocazia și data | validează tipurile de ocazii |
| Limba preferată | validează distribuția RO/RU |
| Contact (Telegram / WhatsApp / email) | livrarea |

**Tehnic:** Laravel simplu, sau chiar un formular + Google Sheets. Nu folosi repo-ul Wishio — nu amesteca validarea cu produsul. Analytics: PostHog sau Plausible.

#### Ziua 5 — Distribuție *(3 h)*
- Grupuri Facebook din Moldova: mame, cumpărături, IT, anunțuri locale
- Canale Telegram locale
- r/moldova
- Cercul personal — **cere-le să distribuie, nu doar să completeze**

Postare onestă, la persoana întâi: *„Construiesc ceva și vreau să testez o idee. Dacă ai un cadou de luat în perioada următoare, scrie-mi despre persoană și îți trimit 5 idei concrete, gratis."* Funcționează mai bine decât orice text de reclamă.

### Săptămâna 2 (15 h)

#### Zilele 6–10 — Livrarea *(10 h)*

Pentru fiecare cerere, în maximum 24 de ore:
1. Caută de mână pe Magaziner, Darwin, Bomba, Ultra, Enter
2. Alege **5 produse**, din categorii diferite, în bugetul cerut
3. Trimite în limba cerută, formatat curat:

> 🎁 **Idei pentru [nume], [buget] MDL**
>
> **1. [Produs]** — [preț] MDL · [magazin]
> *De ce: [un rând]*
> [link]
>
> …
>
> Care ți-a plăcut? Ai cumpărat ceva până la urmă?

4. **Notează în foaie**, pentru fiecare: cât ai căutat, ce categorii ai încercat și ai respins, ce a răspuns, dacă a dat click, dacă a cumpărat.

**Notează și textul brut al câmpului „ce îi place"** — acesta devine intrarea de test pentru `extractPersonTraits()` la S7.

#### Zilele 8–10, în paralel — Comercianții *(3 h)*
- Cere-i proprietarului Magaziner o introducere la 2–3 magazine
- Întrebarea: *„Dacă îți aduc 500 de clickuri pe lună de la oameni care caută cadou, cu buget declarat și dată fixă, cât ar valora asta pentru tine?"*
- **Lasă-i pe ei să spună cifra.** Nu sugera tu un preț.
- Pune-i proprietarului cele trei întrebări de piață din `docs/12 § 1`

#### Serile 8–12, când ai chef — C1 *(2 h + continuare)*
Începe **tabelul de onomastici**. Nu depinde de nimic și e util indiferent de rezultat. Singurul lucru pe care merită să-l construiești înainte de porți.

#### Ziua 11 — Evaluarea *(2 h)*

| Reper | Prag | Cifra ta |
|---|---|---|
| **G0** | 150+ vizitatori, 20+ cereri, la cost 0 | ___ |
| **G1** | din 20 de recomandări: **10+** „asta chiar aș lua", **5+** au dat click | ___ |
| **G2** | **2 din 7** comercianți spun o cifră concretă | ___ |

---

## 5. Ce faci cu rezultatul

| Rezultat | Ce înseamnă | Ce faci |
|---|---|---|
| toate trei ✅ | ipotezele țin | mergi la MVP cu încredere |
| G0 ✅ G1 ✅ G2 ❌ | oamenii vor, comercianții nu plătesc **încă** | construiește; monetizarea se rezolvă cu tracțiune în mână |
| G0 ✅ G1 ❌ | vin, dar recomandările nu-i conving | cea mai utilă informație din toată faza — vezi mai jos |
| G0 ❌ | nimeni nu cere ajutor | mesajul sau canalul; reformulează și mai încearcă |

**Dacă G1 e sub prag**, întreabă-te care din două:
- *recomandările erau proaste* → problema e catalogul și criteriile. Mai încearcă 10, cu ce ai învățat.
- *recomandările erau bune, dar oamenii tot n-au cumpărat* → frâna e altundeva: preț, încredere, timing. Merită înțeleasă.

Diferența se vede din ce ți-au răspuns. De asta notezi totul.

## 6. Ce NU faci în Faza 0

| Nu | De ce |
|---|---|
| Nu scrii cod în repo-ul Wishio | validarea și produsul sunt lucruri separate |
| Nu faci logo, icon, identitate vizuală | testezi mesajul |
| Nu integrezi AI | tu ești AI-ul, și de aceea înveți |
| Nu plătești reclamă | dacă e nevoie de bani ca să vină, ai deja un răspuns |
| Nu construiești aplicația mobilă | evident, dar e cel mai greu de respectat |
| Nu extinzi peste 20 de recomandări | 20 sunt suficiente pentru semnal; peste, amâni decizia |
