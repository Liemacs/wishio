# 03 — Scop: ce construim, în ce ordine, și ce NU construim

---

## Faza 0 — Validare fără aplicație (2 săptămâni)

**Scopul:** să afli dacă cineva vrea „help me choose” **înainte** să scrii 6 luni de cod.
Planul inițial sărea direct în 7 sprinturi. Aceasta este cea mai ieftină asigurare disponibilă.

**Ce construiești (3–4 zile de muncă):**
- O pagină, în **RO / RU / EN**, cu comutator de limbă: *„Ai o zi de naștere în curând și n-ai idee ce să iei? Scrie-ne despre persoană, îți trimitem 5 idei reale, cu preț și magazin din Moldova. Gratis.”*
- Formular: despre cine e vorba, vârstă, relație, ce îi place, buget, data ocaziei, limbă preferată, contact (Telegram/WhatsApp/email).
- Analytics: PostHog sau Plausible.
- Fără backend serios. Laravel simplu, sau chiar un form + Airtable/Sheets.

**Ce faci manual (concierge, 10 zile):**
- Pentru fiecare cerere, cauți **de mână** 5 produse reale pe Magaziner / Darwin / Bomba / Ultra.
- Trimiți un mesaj cu 5 carduri (poză, preț, magazin, link), **în limba aleasă de utilizator**.
- Întrebi: *„Care ți-a plăcut? Ai cumpărat?”*
- Notezi absolut tot: timp per recomandare, ce categorii cer, ce bugete, ce respinge, ce cuvinte folosesc — **acesta este corpusul de antrenament pentru promptul real.**

**În paralel (asta e jumătatea importantă):**
- Email/apel la **Magaziner** — parteneriat feed + revenue share (R4).
- Discuție cu **5 comercianți** și **2 restaurante** — întrebarea din R5: ai plăti?
- **Testul contactelor (R1):** 10 telefoane reale, numără câte contacte au ziua de naștere completată. Notează cifra. Decide onboarding-ul pe baza ei.
- Verificare **nume + domeniu + marcă**.

**Porți:** G0, G1, G2 din `docs/02-strategie-riscuri.md`.
**Cost:** ~0 MDL, ~40 de ore.
**Ce poate omorî proiectul aici:** dacă din 20 de recomandări manuale nimeni nu spune „asta chiar aș lua” — produsul nu are miez și ai economisit 6 luni.

---

## MVP (v1) — „Ocazii + Cadouri”

**Obiectiv:** demonstrează bucla completă `ocazie → reminder → profil → recomandare → click spre magazin`, în trei limbi.
**Durată realistă pentru un dezvoltator part-time:** 10–14 săptămâni. Vezi `PLAN.md`.

### Include

**Cont & i18n**
- Autentificare: Apple, Google, email OTP (fără parolă — mai puțină fricțiune, mai puțin de securizat)
- Selector de limbă RO/RU/EN la primul ecran, cu detectare automată din locale; schimbabil oricând
- Tot conținutul (UI, emailuri, push, pagini publice, texte AI) livrat în limba aleasă

**Persoane**
- Import din Contacts (nume, ziua de naștere dacă există, poza rămâne pe device)
- **Name-day resolver** — onomastica derivată automat din prenume (arma de cold-start)
- Adăugare/editare manuală: nume, dată, relație, interese, note, buget
- Onboarding AI: o frază liberă despre persoană → interese structurate, pe care userul le confirmă

**Ocazii**
- Zile de naștere, onomastici, sărbători cu cadouri (8 Martie, 1 Iunie, Crăciun, Paște), aniversări personalizate
- Calendar de ocazii, grupat: azi / săptămâna asta / luna asta / mai târziu

**Remindere**
- Push la 7 / 3 / 1 zile, configurabile, cu oră preferată
- Email digest săptămânal (fallback pentru cine refuză push)
- Widget cu următoarea ocazie

**Recomandări**
- Buget + tip (cadou / experiență) → 5–8 sugestii din catalog real
- Motiv scurt pentru fiecare („pentru că îi plac mașinile”)
- Salvare ca idee, marcare „i-am luat asta”
- Catalog: **`MagazinerCatalog`** — ~70.000 produse, cu stratul de curatare din `docs/05 § 4`. Doar `gift_score >= 3` intră în recomandări.

**Profilul meu**
- Ziua mea, interese, „ce îmi doresc” (produse) și „unde aș vrea să merg” (locuri/experiențe)
- Setări de vizibilitate per câmp

**Link personal public** ← *feature-ul de creștere, nu un extra*
- `wishio.md/@maxim` — pagină web, indexabilă, în RO/RU/EN, deschisă fără cont și fără app
- Prietenul completează ziua lui + câteva interese → datele ajung la tine **cu consimțământul lui**
- La final: prompt de instalare

**Istoric cadouri**
- Ce a primit persoana, în ce an, de la tine — folosit ca filtru anti-repetare în recomandări

### Nu include în MVP (explicit)

❌ Chat între utilizatori · ❌ Feed social / followers · ❌ Group gifting cu colectare de bani · ❌ Plăți în aplicație · ❌ Experience booking · ❌ Merchant self-service dashboard · ❌ Scraping propriu · ❌ Marketplace propriu · ❌ Enrichment automat între utilizatori (interzis — vezi R2) · ❌ Aplicație web completă (doar paginile publice de profil) · ❌ Desktop

---

## v1.1 — Experiențe (4–6 săptămâni după MVP)

- `Experience Engine`: restaurante, karting, escape room, bowling, spa, cinema, evenimente
- Filtre: număr de persoane, buget total, oraș, tip
- Contact/rezervare prin telefon sau link extern (fără plată în aplicație)
- 30–60 de locații din Chișinău, introduse manual, cu descrieri în RO/RU/EN

Motivul pentru care e imediat după MVP: *„unde sărbătorim”* este un job la fel de nerezolvat ca *„ce îi iau”*, și are ticket mediu mult mai mare.

---

## v2 — Monetizare și rețea

- **Merchant dashboard**: impresii, clickuri, produse top, campanii sezoniere, facturare
- **Poziții sponsorizate**, marcate clar
- **Group gifting** — inițial *fără* colectare de bani (împărțire + coordonare + „cine cât dă”), pentru că plățile online în Moldova sunt complicate (Stripe nu e oficial disponibil; alternativele reale sunt maib Mastercard Gateway și paynet). Colectarea de bani abia după ce e clar că funcționează coordonarea.
- **Rezervare cadou anti-duplicat** între utilizatori — cu design care nu expune identitatea
- **Wishlist public partajabil** (pagina de profil devine wishlist real)

---

## v3 — Extindere și platformă

- **România** (piața reală: 19 mil., afiliere matură prin 2Performant/Profitshare/eMAG)
- **B2B**: HR/office manager — calendarul de ocazii al echipei, bugete, aprobări
- **Gift Graph** — recomandări care se îmbunătățesc din comportamentul agregat
- Integrare calendar (Google/Apple) bidirecțională
- API pentru comercianți

---

## Anti-scop permanent

Lucruri pe care le refuzăm chiar dacă par tentante:

| Nu facem | De ce |
|---|---|
| Rețea socială cu feed | schimbă produsul, atrage moderare, ucide încrederea |
| Enrichment silențios între useri | ilegal sub Legea 195/2024 + respingere App Store 5.1.2 |
| Notificări zilnice | reminderul își pierde puterea; max ~4 push-uri per ocazie |
| Catalog uriaș needitat | recomandările proaste sunt mai rele decât nicio recomandare |
| AI care inventează produse | distruge încrederea irecuperabil |
| Subscription la lansare | nu ai dreptul să ceri bani înainte să dovedești valoarea |
| Localizare „adăugăm RU mai târziu” | în Moldova, RU lipsă = jumătate din piață pierdută |
