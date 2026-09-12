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

## 6. Gesturi — referința: selectorul de oră

Primul element tras cu degetul e rigla de ore din setările de notificări, `src/features/notifications/HourRuler.tsx`. Orice control nou tras cu degetul pornește de la ea.

| Principiu | Cum e făcut |
|---|---|
| Urmărire 1:1 | `translationX` intră direct în poziție, pe firul UI, fără nicio animație între deget și riglă |
| Predarea vitezei | arcul de fixare pornește cu `velocity` luată din gest, deci nu există salt la eliberare |
| Proiecția impulsului | ținta = poziția + v/1000 · d/(1−d), cu d = 0,998 (decelerarea derulării iOS), apoi cea mai apropiată valoare permisă |
| Rezistență elastică | formula de rubber band a iOS la capete, cu coeficientul 0,55 |
| Întreruptibilitate | atingerea oprește rigla din mers: `cancelAnimation` la `onBegin`, nu la activarea gestului |
| Conflict cu derularea | `activeOffsetX` + `failOffsetY`: un gest vertical rămâne al paginii |
| Haptică | un singur tic la fiecare valoare trecută, nu continuu |
| Accesibilitate | un singur element `adjustable`, cu acțiuni de creștere și scădere; butoanele vizuale sunt ascunse de VoiceOver |
| Mișcare redusă | fără arc și fără scalare; rămân opacitatea și haptica |
| Margini | capetele riglei se estompează: conținutul continuă dincolo de ce se vede |

## 7. Ce rămâne de făcut

| | |
|---|---|
| Materiale translucide (`expo-blur`) | antetele sunt opace; blur-ul e instalat, nefolosit |
| Efecte de margine la scroll pe liste lungi | rigla le are; listele încă folosesc separatorul |
| Sheet-uri cu mai multe poziții | când apar, refolosesc proiecția și predarea vitezei de mai sus |
