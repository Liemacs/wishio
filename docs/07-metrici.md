# 07 — Metrici, evenimente și porți

> Schema de evenimente se scrie **înainte** de cod. Analytics adăugat la final produce date inutile.

---

## 1. North Star

**Ocazii acționate per utilizator activ, pe trimestru.**

*Ocazie acționată* = utilizatorul a deschis o ocazie **și** a făcut ceva: a cerut recomandări, a salvat o idee, a dat click spre magazin, sau a marcat un cadou ca oferit.

De ce aceasta și nu „utilizatori activi”: măsoară exact valoarea promisă — nu „a deschis app-ul”, ci „l-am ajutat să rezolve ceva”. Un reminder deschis și ignorat valorează zero.

Țintă la 6 luni: **≥ 2,5 ocazii acționate / user activ / trimestru.**

---

## 2. Funnel-ul complet

```
Instalare
  │  → install_completed
  ▼
Cont creat                                  țintă  > 70%
  │  → signup_completed {method, locale}
  ▼
Persoane adăugate ≥ 5                       țintă  > 60%   ◄ G3
  │  → people_added {count, source}
  ▼
Ocazii în calendar ≥ 8                      țintă  > 70%
  │  → occasions_ready {birthdays, name_days}
  ▼
Push activat                                țintă  > 55%
  │  → push_permission {granted}
  ▼
Primul reminder trimis
  │  → reminder_sent
  ▼
Reminder deschis                            țintă  > 35%   ◄ G4
  │  → reminder_opened {days_before, type}
  ▼
Recomandări cerute                          țintă  > 20%   ◄ G5
  │  → reco_requested {budget, kind}
  ▼
Click spre magazin                          țintă  > 15%   ◄ G5
  │  → outbound_click {merchant, price}
  ▼
Cadou marcat ca oferit                      țintă  > 8%
     → gift_marked_given
```

---

## 3. Schema de evenimente

Convenție: `snake_case`, verb la trecut, proprietăți fără PII, `locale` pe fiecare eveniment.

| Eveniment | Proprietăți cheie |
|---|---|
| `app_opened` | `source` (push / widget / direct / deeplink), `locale` |
| `language_selected` | `locale`, `was_auto_detected` |
| `signup_completed` | `method`, `locale` |
| `contacts_permission` | `granted`, `contacts_total`, `with_birthday`, `with_birthday_pct` ← **măsoară R1 în producție** |
| `people_added` | `count`, `source` (contacts / manual / link / name_day) |
| `name_days_resolved` | `resolved`, `confirmed`, `rejected` |
| `occasion_confirmed` | `type`, `source`, `confidence` |
| `push_permission` | `granted`, `prompt_position` |
| `reminder_sent` | `type`, `days_before`, `channel`, `locale` |
| `reminder_opened` | `type`, `days_before`, `hours_since_sent` |
| `reco_requested` | `kind`, `budget_min/max`, `person_interests_count`, `locale` |
| `reco_shown` | `run_id`, `items`, `latency_ms`, `has_ai_reasons` |
| `reco_item_tapped` | `rank`, `category`, `price`, `is_sponsored` |
| `outbound_click` | `merchant_id`, `product_id`, `price`, `rank`, `context` ← **evenimentul de venit** |
| `gift_idea_saved` / `gift_marked_given` | `person_id_hash`, `occasion_type`, `price` |
| `profile_link_shared` | `channel` |
| `profile_link_opened` | `locale`, `is_new_visitor` |
| `profile_submission` | `fields_filled`, `converted_to_install` ← **măsoară K-factor** |
| `wishlist_item_added` | `kind`, `visibility` |
| `ai_consent` | `granted` |

**Obligatoriu:** `person_id` nu se trimite niciodată brut către analytics — doar hash. Numele nu se trimit deloc.

---

## 4. Metrici operaționale

| Metrică | Prag de alarmă |
|---|---|
| Latență p95 generare recomandări | > 8 s |
| Cost AI / recomandare | > 0,02 USD |
| % recomandări cu 0 rezultate | > 10% → catalogul e prea mic |
| % produse fără stoc la click | > 5% → sync-ul e stricat |
| Rată de livrare push | < 90% |
| Recomandări respinse de validare (halucinație) | > 0 → **bug critic** |
| Onomastici respinse de useri | > 25% → tabelul de aliasuri e prost |
| Erori de traducere lipsă (cheie brută afișată) | > 0 → bug |

---

## 5. Segmentare pe limbă (specific Moldova)

Raportează **totul** defalcat pe `locale`. Întrebări la care trebuie să poți răspunde după prima lună:
- Care e distribuția RO / RU / EN a utilizatorilor?
- Diferă rata de activare între RO și RU? (dacă da → problemă de copy sau de catalog, nu de produs)
- Care limbă are K-factor mai mare?
- Catalogul acoperă la fel de bine ambele segmente?

Dacă RU reprezintă 40% din useri dar 15% din clickuri, ai o problemă de conținut, nu de produs. Fără segmentare pe limbă nu o vezi niciodată.

---

## 6. Tablou săptămânal (5 cifre, atât)

1. Utilizatori activi săptămânal
2. Ocazii acționate
3. Clickuri spre magazin
4. Instalări noi din linkuri personale (viralitate)
5. D30 retenție pe cohorta lunii trecute

Restul sunt pentru debugging, nu pentru decizii.
