# 12 — Integrarea cu Magaziner: ce să ceri și ce să agreezi

> Pregătit ca să-l poți trimite direct. Secțiunea 1 este documentul de înțelegere, secțiunea 2 este specificația tehnică.
> Relația e bună — tocmai de aceea merită pusă pe hârtie acum, cât e totul simplu.

---

## 1. Înțelegerea — o pagină, nu un contract de 20

Nu ai nevoie de avocat pentru asta. Ai nevoie ca peste doi ani să existe un document care spune ce s-a înțeles.

| Punct | De ce contează | Propunere |
|---|---|---|
| **Ce oferă Magaziner** | claritate | acces API la catalogul de produse: date, prețuri, disponibilitate, linkuri către magazine |
| **Ce oferă Wishio** | reciprocitate | trafic calificat, cu intenție de cumpărare și buget declarat; atribuire clară a sursei |
| **Cine deține datele** | esențial | produsele rămân ale Magaziner. Wishio le cachează pentru funcționare, nu le republică drept catalog propriu |
| **Tracking de clickuri** | bani | fiecare click iese cu parametru de sursă, ca ei să poată atribui traficul |
| **Împărțirea veniturilor** | evită neplăcerile | dacă apar venituri din afiliere sau sponsorizări, cum se împart. **Chiar dacă răspunsul e „vedem mai târziu", scrie-l** |
| **Durată și ieșire** | ambele părți | cine poate opri, cu ce preaviz (sugestie: 60 de zile), ce se întâmplă cu datele cachate la final |
| **Exclusivitate** | nu o cere | nu ai nevoie de ea și îngreunează discuția |
| **Fără garanții de disponibilitate** | onestitate | e un parteneriat, nu un serviciu plătit. Nu cere SLA; în schimb, construiește-ți toleranță la indisponibilitate |

**Trei întrebări pe care merită să i le pui, dincolo de API** — cunoaște piața mai bine decât oricine:
1. Plătesc efectiv magazinele din Moldova pentru trafic? Cât, și pentru ce model? *(rezolvă B3)*
2. Există vreo infrastructură reală de afiliere cu tracking în MD? *(rezolvă B5)*
3. Poate să te prezinte la 2–3 magazine cu care are relație bună?

---

## 2. Specificația tehnică

### 2.1 Ce avem nevoie, minim

Pentru fiecare produs:

| Câmp | Obligatoriu | Observație |
|---|---|---|
| `id` | ✅ | identificator stabil — **nu trebuie să se schimbe între sincronizări** |
| `title` | ✅ | |
| `description` | ✅ | chiar scurtă; alimentează maparea pe interese |
| `brand` | ✅ | **important** — folosit la deduplicare și la `gift_score` |
| `category` | ✅ | id + denumire, plus ierarhia dacă există |
| `price`, `currency` | ✅ | |
| `old_price` | opțional | util pentru „la reducere" |
| `image_url` | ✅ | una principală; mai multe dacă există |
| `deeplink` | ✅ | link direct către produs, la magazin |
| `merchant_id`, `merchant_name` | ✅ | |
| `in_stock` | ✅ | |
| `updated_at` | ✅ | **critic pentru sync delta** |
| `ean` / `sku` | dacă există | rezolvă deduplicarea aproape perfect — merită cerut explicit |

### 2.2 Forma preferată a API-ului

```
GET /api/products?updated_since=2026-09-12T10:00:00Z&page=1&per_page=500
```

**Ce contează cel mai mult, în ordine:**
1. **`updated_since`** — sincronizare delta. Fără el, tragi 70.000 de produse la fiecare 6 ore, degeaba, și îi încarci serverul.
2. **Paginare cu cursor sau pagină**, cu `per_page` de cel puțin 500.
3. **Un endpoint de categorii** — `GET /api/categories` cu ierarhia. Necesar ca să mapăm taxonomia noastră de interese pe a lor.
4. **Autentificare prin cheie API** în header. Simplu.
5. **Limită de rată declarată** — ca să nu-l lovim accidental.

Dacă API-ul e mai greu de făcut, **un export zilnic JSON/XML pe un URL privat este suficient pentru MVP**. Nu bloca proiectul pe forma ideală. Delta e o optimizare, nu o condiție.

### 2.3 Tracking-ul de clickuri

Propunerea corectă, care îi convine și lui:

```
https://magaziner.md/out/{product_id}?src=wishio&ref={click_id}
```

- fiecare click din Wishio trece prin redirectul lor, cu sursă marcată
- noi păstrăm `click_id` în `outbound_clicks` și putem reconcilia lunar
- el vede exact cât trafic aduci — ceea ce este **argumentul tău comercial**, nu doar o formalitate tehnică

### 2.4 Ce NU cerem

- nu cerem date despre utilizatorii lui
- nu cerem statistici despre alte surse de trafic
- nu cerem exclusivitate
- nu republicăm catalogul ca produs propriu și nu-l expunem prin API-ul nostru

Merită spus explicit în înțelegere. Scade orice suspiciune.

---

## 3. Ce construim noi peste API

Ca să fie clar de ce parteneriatul are sens pentru amândoi: **Magaziner răspunde la „unde e mai ieftin". Wishio răspunde la „ce să-i iau lui Alex".** Sunt două întrebări diferite, pe același catalog.

| Strat | Al cui |
|---|---|
| Catalog, prețuri, disponibilitate, linkuri | Magaziner |
| Deduplicare produs/oferte | Wishio |
| Filtru „poate fi cadou" | Wishio |
| `gift_score` 1–5 | Wishio |
| Mapare pe taxonomia de interese | Wishio |
| Profilul persoanei și ocaziile | Wishio |
| Motorul de recomandare și explicațiile | Wishio |

---

## 4. Toleranța la indisponibilitate

Parteneriatul e pe bunăvoință, iar API-ul lui poate cădea. Produsul nu trebuie să cadă odată cu el.

- catalogul se **cachează local** — recomandările merg și dacă API-ul e jos
- `last_successful_sync` monitorizat; alertă dacă depășește 24 h
- dacă prețul unui produs e mai vechi de 48 h, marchează-l „preț orientativ"
- dacă un deeplink dă 404, ascunde oferta și loghează
- `CatalogAdapter` rămâne abstract — o a doua sursă se adaugă fără să atingi Recommendation Engine

---

## 5. Pașii, în ordine

1. Trimite-i secțiunea 2.1 și 2.2 — sunt cerințe concrete, ușor de evaluat
2. Întreabă ce e simplu și ce e greu de partea lui; **acceptă varianta simplă**
3. Pune înțelegerea din secțiunea 1 pe o pagină, semnată sau măcar confirmată prin email
4. Cere un set de test: 500 de produse, ca să începi maparea taxonomiei înainte de API-ul complet
5. Pune-i cele trei întrebări de piață din secțiunea 1
