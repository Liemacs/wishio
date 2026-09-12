# 19 — Fundamentele de design

> Principiile Apple de interfață și mișcare, traduse pentru React Native.
> Sursa: *Designing Fluid Interfaces*, *The Details of UI Typography*, *Principles of Great Design*.

---

## 1. Ideea de bază

O interfață se simte vie când mișcarea **pornește din valoarea curentă de pe ecran**, moștenește viteza utilizatorului, proiectează impulsul înainte și poate fi prinsă și inversată în orice moment.

Arcurile sunt unealta care face asta natural, pentru că sunt prin construcție întreruptibile și conștiente de viteză.

---

## 2. Mișcare — `src/design/motion.ts`

### Doi parametri, nu trei

| Parametru | Ce controlează |
|---|---|
| **Raport de amortizare** | cât sare. `1.0` = fără depășire. `0.8` = salt ușor |
| **Răspuns** | cât de repede ajunge la țintă, în ms |

Masa, rigiditatea și amortizarea fizică sunt detalii de implementare. Se gândește în cei doi de mai sus.

### Presetările

| Preset | Amortizare | Răspuns | Unde |
|---|---|---|---|
| `default` | 1.0 | 400 ms | repoziționări, apariții, comutări |
| `press` | 1.0 | 250 ms | apăsarea unui buton |
| `momentum` | 0.8 | 400 ms | **doar** după un gest cu viteză |
| `sheet` | 0.8 | 300 ms | panouri și sheet-uri |

> **Regula care contează:** salt doar acolo unde gestul însuși a purtat impuls. O depășire pe un meniu care doar a apărut se simte greșit; aceeași depășire pe un card pe care l-ai aruncat se simte corect.

### Feedbackul e la apăsare, nu la eliberare

`Button` reacționează pe `onPressIn`. Un buton care așteaptă ridicarea degetului se simte mort, oricât de rapid ar fi restul aplicației.

---

## 3. Mișcare redusă — nu înseamnă „fără feedback"

Înseamnă un **echivalent mai blând, fără componentă vestibulară**:

| Se păstrează | Se elimină |
|---|---|
| tranziții de opacitate | deplasări și scalări |
| schimbări de culoare | depășiri și salturi |
| **haptica** — nu e mișcare vestibulară | decalajul pe listă |

`useReducedMotion()` citește preferința sistemului și o urmărește în timp real; `entrance(reduced, index)` întoarce configurația potrivită.

Arcurile primesc și `ReduceMotion.System`, ca Reanimated să scurteze consecvent tot ce trece pe lângă noi.

---

## 4. Tipografie — `src/design/typography.ts`

**Spațierea dintre litere depinde de mărime. O valoare fixă e greșită undeva.**

Textul mare are nevoie de spațiere **negativă**: pe măsură ce crește, literele par prea depărtate. Textul mic are nevoie de puțină spațiere pozitivă, pentru lizibilitate. Interlinia merge invers — strânsă la titluri, lejeră la text de citit.

| Scară | Mărime | Interlinie | Spațiere |
|---|---|---|---|
| `display` | 34 | 38 | **−0.8** |
| `title` | 28 | 33 | −0.5 |
| `heading` | 20 | 25 | −0.2 |
| `body` | 16 | 24 | 0 |
| `footnote` | 13 | 18 | +0.1 |
| `caption` | 11 | 14 | **+0.3** |

Ierarhia se construiește din **greutate + mărime + interlinie ca set**, nu din mărime singură.

---

## 5. Reguli de urmat de acum înainte

1. **Fără animații cu durată fixă pe ceva ce utilizatorul poate atinge.** Arcuri, ca să poată fi întrerupte.
2. **Feedback la apăsare, continuu pe durata gestului.** Nu doar la final.
3. **Intrare și ieșire pe același drum.** Ce apare din dreapta, dispare spre dreapta.
4. **Haptică doar la momente care contează** — succes, eroare, comitere, fixare. Feedbackul în exces îi învață pe oameni să-l ignore.
5. **Vizualul, sunetul și haptica pe același cadru.** Latența dintre ele strică iluzia.
6. **Fiecare valoare de spațiere, timp și aliniere e o decizie pe care o poți apăra.** Nimic la întâmplare.

---

## 6. Ce rămâne de făcut

| | |
|---|---|
| Gesturi 1:1 cu urmărirea degetului | nu avem încă niciun element tras cu degetul |
| Predarea vitezei la finalul gestului | idem |
| Proiecția impulsului pentru fixare | la sheet-uri cu mai multe poziții |
| Rezistență elastică la margini | listele folosesc comportamentul implicit, care e deja corect |
| Materiale translucide (`expo-blur`) | antetele sunt opace; blur-ul e instalat, nefolosit |
| Efecte de margine la scroll | în locul separatorului de 1px |

Primele trei devin relevante când apare primul element tras cu degetul — cel mai probabil selectorul de buget sau un sheet cu poziții multiple.
