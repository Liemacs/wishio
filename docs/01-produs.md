# 01 — Produs: viziune, poziționare, bucle

> Status: draft de lucru. Înlocuiește poziționarea „Birthday Reminder App” din conversația inițială.

---

## 1. Promisiunea

**Wishio — nu uiți niciodată o ocazie și știi exact ce să faci.**

EN: *Never miss an occasion. Always know what to give.*

Reminder-ul este **mecanismul de retenție**, nu produsul.
Produsul este **decizia**: „ce iau / unde mergem / cât costă / de unde cumpăr”.

---

## 2. Problema reală (jobs-to-be-done)

| # | Job | Frecvență | Cine îl rezolvă azi | Cât de prost |
|---|-----|-----------|---------------------|--------------|
| J1 | „Să nu uit ziua lui X” | lunar | Calendar, Facebook, memorie | rezolvat parțial, gratuit |
| J2 | „Ce naiba îi iau?” | 5–15×/an | Google, prieteni, panică | **nerezolvat** |
| J3 | „De unde iau, în bugetul meu, în orașul meu?” | la fiecare cadou | Instagram, magazine random | **nerezolvat local** |
| J4 | „Unde sărbătorim, pentru N oameni, cu bugetul B?” | 3–6×/an | grup WhatsApp, haos | **nerezolvat local** |
| J5 | „Ce i-am luat anul trecut?” | la fiecare repetare | nimic | nerezolvat |

**Concluzie:** J1 este cârligul (gratuit, ușor de copiat). J2–J4 sunt produsul (greu, local, monetizabil).
Dacă produsul se oprește la J1, este o aplicație de calendar și moare.

---

## 3. Trei reframe-uri față de planul inițial

### Reframe 1 — Occasion Engine, nu Birthday Engine

Zilele de naștere singure dau ~1 eveniment / persoană / an și o distribuție prea rară ca să susțină retenție.
Moldova are un **calendar cultural de ocazii mult mai dens**, ignorat total de aplicațiile globale:

| Tip ocazie | Exemple | De ce contează |
|---|---|---|
| Zile de naștere | — | baza |
| **Onomastică / ziua numelui** | Sf. Vasile, Sf. Ion, Sf. Gheorghe, Sf. Constantin și Elena, Sf. Maria, Sf. Dumitru, Sf. Mihail, Sf. Nicolae, Sf. Ștefan | **derivabilă automat din prenumele din Contacts** — nu necesită nicio dată introdusă de user. Uriaș pentru cold-start. |
| Sărbători cu cadouri obligatorii | 8 Martie, 1 Iunie, Crăciun, Paște, Ziua Profesorului (5 oct), 1 Septembrie | volum sezonier predictibil |
| Aniversări de relație | căsătorie, „împreună de X ani” | introduse manual, retenție mare |
| Evenimente de viață | nuntă, cumătrie, absolvire, casă nouă, mutare, promovare | valoare mare per cadou |
| Corporate | ziua colegului, ziua companiei, onboarding | vector B2B |

**Impact:** de la ~1 ocazie/persoană/an la **3–5 ocazii/persoană/an**, și elimină problema de sezonalitate care a omorât ideea Secret Santa.

> Onomastica este cel mai bun activ de cold-start pe care îl are acest produs.
> Din 200 de contacte, poate 8 au ziua de naștere completată. Dar ~150 au prenume.
> `prenume → sfânt → dată` este un tabel static, făcut o singură dată, care generează instant un calendar plin.
> Vezi `docs/04-model-domeniu.md § Name-day resolver`.

### Reframe 2 — Link-ul personal este motorul de creștere, nu importul din Contacts

Planul inițial presupune că Contacts conține zile de naștere. **În realitate nu conține.** Câmpul `birthday` este completat în general pentru sub 10% din contacte, și aproape deloc pe Android.

Bucla corectă:

```
Eu instalez → import contacte (nume + ce există)
     ↓
Wishio îmi zice: "știm ziua doar pentru 6 din 143 de persoane"
     ↓
"Trimite-le linkul tău" → wishio.md/@maxim
     ↓
Prietenul deschide linkul (fără cont, fără app)
     ↓
Vede: "Maxim vrea să știe ce să-ți ia de ziua ta"
     ↓
Completează: ziua + 3 lucruri care îi plac
     ↓
La final: "Vrei și tu să nu uiți zilele prietenilor tăi?" → install
     ↓
Noul user importă contactele lui → ciclul se reia
```

Asta rezolvă simultan: cold-start-ul datelor, calitatea datelor (**vin de la sursă, nu ghicite**), problema legală (persoana consimte ea însăși) și achiziția virală. **Este cea mai importantă decizie de produs din tot documentul.**

### Reframe 3 — Produs cu două fețe, de la început

| Latura | Utilizator | Valoare | Plătește? |
|---|---|---|---|
| Consumer | cel care dă cadoul | decizie + descoperire | nu |
| **Merchant** | magazin / restaurant / activitate | trafic calificat, cu intenție, cu buget declarat și dată fixă | **da** |

Intenția pe care o are Wishio este extrem de valoroasă pentru comercianți:
*„bărbat, 27 ani, pasionat de mașini și gaming, buget 1000–1500 MDL, cumpără în următoarele 5 zile”*.
Asta nu se găsește în Google Ads local. Merchant dashboard-ul nu e „faza 3”, e **produsul care aduce banii** — vezi `docs/03-scop.md`.

---

## 4. Publicul țintă

**ICP primar (MVP):** 22–40 ani, urban (Chișinău, Bălți), smartphone, cumpără 5+ cadouri/an, bilingv RO/RU.
**ICP secundar:** cupluri (cel mai mare volum de ocazii), și „conectorul” dintr-un grup de prieteni — cel care organizează.
**ICP B2B (faza 2):** HR / office manager în companii de 20–200 de angajați (IT, bănci, retail) — bugete de cadouri recurente, zero unelte.

**Nu targetăm la MVP:** adolescenți (fără buget), 50+ (fricțiune la instalare), diaspora (catalog irelevant — dar e piața #3).

---

## 5. Diferențiere

| Concurent | Ce face bine | Unde pierde față de noi |
|---|---|---|
| Calendar / Contacts nativ | gratuit, zero efort | doar reminder, zero acțiune |
| Facebook / Instagram | zile de naștere gratis | în declin, nu toți mai sunt acolo, zero comerț local |
| Elfster, Drawnames | wishlist + Secret Santa matur | sezonier, catalog US, zero relevanță MD |
| Giftster, Throne, Wishlistr | wishlist bun | catalog global, fără reminder proactiv, fără experiențe |
| „AI gift finder”-e generice (ChatGPT inclus) | idei nelimitate | **halucinează produse care nu există local**, nu știu prețul, nu știu unde cumperi, nu au memorie despre persoană |
| Magaziner | catalog local real, prețuri, 15+ magazine | e un motor de comparație, nu știe *pentru cine* cumperi |

**Poziția noastră, într-o frază:**
> Singura aplicație care știe *cine sunt oamenii tăi*, *când sunt ocaziile* și *ce poți efectiv cumpăra pentru ei, azi, în Moldova, în bugetul tău*.

Bariera defensibilă nu e AI-ul (e comoditate). Bariera este **Gift Graph-ul**: profilul acumulat al fiecărei persoane + istoricul cadourilor + catalogul local mapat pe interese. Se construiește în timp și nu se poate copia cu un prompt.

---

## 6. Buclele produsului

### Buclă de achiziție (virală)
`user nou → link personal trimis în WhatsApp/Telegram → prieten completează → prompt de install → user nou`
Metrică: **K-factor**. Țintă realistă: 0.35–0.6 la lansare. Sub 0.2 = achiziția trebuie plătită.

### Buclă de retenție
`ocazie detectată → push cu context ("Ion are onomastica în 5 zile") → deschide → recomandări → acțiune → gift history → recomandări mai bune data viitoare`
Metrică: **ocazii/user/trimestru** și **% remindere deschise**.

### Buclă de date
`mai multe ocazii completate → profil mai bogat → recomandări mai bune → mai multă folosire → mai multe date`

### Buclă de monetizare
`click spre magazin → comision/CPC → merchant vede conversii în dashboard → cumpără promovare → catalog mai bogat → recomandări mai bune`

---

## 7. Principii de produs (reguli pentru orice decizie viitoare)

1. **Zero muncă la onboarding.** Dacă userul trebuie să tasteze 20 de zile de naștere, am pierdut. Contacts + onomastică + link.
2. **AI-ul nu inventează niciodată un produs.** Sugerează doar din catalog real, cu preț real și magazin real. Vezi `docs/05-arhitectura.md § Anti-halucinație`.
3. **Nicio informație privată a unui user nu ajunge la alt user.** Nici măcar indirect („cineva a rezervat X”) fără design explicit.
4. **Nu construim o rețea socială.** Fără feed, fără followers, fără chat public.
5. **Fiecare ecran răspunde la o singură întrebare.** Home = „la cine trebuie să mă gândesc azi?”.
6. **Trilingv din prima zi: RO (limba de bază) + RU + EN.** Nu este o fază ulterioară. Toate stringurile, emailurile, push-urile, paginile publice de profil și textele generate de AI se livrează în toate trei. RO este sursa de adevăr pentru traduceri; RU este obligatoriu pentru piața Moldovei; EN deblochează diaspora și extinderea. Vezi `docs/05-arhitectura.md § i18n`.
7. **Nimic nu blochează request-ul.** AI și sync merg pe cozi.
8. **Fiecare dată are o sursă și un nivel de încredere.** Vezi ierarhia de trust din `docs/04-model-domeniu.md`.
