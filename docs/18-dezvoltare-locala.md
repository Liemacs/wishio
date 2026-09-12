# 18 — Dezvoltare locală

Cum pornești tot, acum, pe mașina ta. Vezi și `docs/00 § D-014`.

---

## 1. Backend

```bash
cd backend
php artisan serve --port=8000
```

Baza de date rulează prin XAMPP (MariaDB). Dacă portul 8000 e ocupat, alege altul și vezi § 3.

```bash
php artisan migrate --seed      # onomastici + taxonomia de interese
php artisan test                # 90 de teste
```

## 2. Aplicația mobilă

```bash
cd mobile
npx expo start
```

Scanezi codul QR cu **Expo Go** pe telefon. Telefonul și calculatorul trebuie să fie în aceeași rețea Wi-Fi.

## 3. Cum găsește aplicația API-ul

`src/api/client.ts` folosește gazda de la care s-a încărcat bundle-ul — adică IP-ul din rețeaua locală al calculatorului tău — și presupune portul **8000**. Nu trebuie să configurezi nimic în cazul obișnuit.

Dacă rulezi API-ul pe alt port sau pe altă mașină:

```bash
EXPO_PUBLIC_API_URL="http://192.168.1.10:8765/api/v1" npx expo start
```

## 4. Ce funcționează acum

| | |
|---|---|
| Autentificare | email + parolă (temporar — Apple/Google/OTP la S1.8) |
| Persoane | listă cu căutare, adăugare, editare, ștergere |
| Interese | selector cu cele 80 de interese, în limba aplicației |
| Limbi | RO / RU / EN, comutabile din ecranul de login |
| Landing Faza 0 | `http://localhost:8000` |

Un flux verificat cap-coadă, cu `curl`:

```
înregistrare cu locale=ru  →  calendar onomastici = stil vechi
creare persoană            →  câmpuri marcate owner_manual, protejate de sync
interese cerute în rusă    →  Аудио и наушники · Фитнес и зал · Автоспорт
aceleași, în română        →  Audio și căști · Fitness și sală · Motorsport
alt utilizator cere persoana mea  →  403
```

---

## 5. ⚠️ Capcană rezolvată: Tailwind nu rula deloc

**Simptom:** `npx expo start` cădea la bundling pe iOS cu
`Unknown at rule: @theme`, urmat de
`failed to deserialize; expected an object-like struct named Specifier`.

**Două cauze suprapuse:**

1. **Lipsea `postcss.config.js`.** NativeWind v5 (`react-native-css`) nu procesează el CSS-ul — îl dă pipeline-ului web al Expo, care rulează PostCSS **doar dacă găsește o configurație**. Fără ea, `@import "tailwindcss"` și `@theme` ajungeau neatinse la lightningcss, care nu le înțelege.

2. **Conflict de `lightningcss` între Tailwind și NativeWind.** Două pachete cer aceeași bibliotecă nativă în versiuni incompatibile:

   | Consumator | Cere | De ce |
   |---|---|---|
   | `@tailwindcss/node` (Tailwind v4) | `1.32.0` **exact** | compilează `@theme` → CSS |
   | `react-native-css` (motorul NativeWind v5) | `>=1.27.0`, dar **rupe pe 1.32.0** | parcurge AST-ul prin API-ul `visitor` |

   npm ridica `1.32.0` la rădăcină, deci NativeWind primea versiunea care îl strică. `react-native-css` trimite regulile înapoi în Rust prin `visitor`, iar formatul AST s-a schimbat în 1.32 → `failed to deserialize; expected an object-like struct named Specifier, found ()`.

   **Soluția — două versiuni în paralel, nu una impusă global:**

   ```json
   "overrides": {
     "@expo/metro-config": { "lightningcss": "1.30.1" },
     "react-native-css":   { "lightningcss": "1.30.1" }
   }
   ```

   Rezultat pe disc: `node_modules/lightningcss` = 1.30.1 (Expo + NativeWind), `node_modules/@tailwindcss/node/node_modules/lightningcss` = 1.32.0 (Tailwind). Un `overrides` global pe `1.30.1` **nu** merge: rupe Tailwind, care are versiunea fixată exact.

   > `npm install` nu rescrie singur arborele când schimbi `overrides`. Secvența care funcționează: `npm install --package-lock-only` (re-rezolvă lockfile-ul), apoi `npm ci` (reconstruiește `node_modules` exact după el).

> **Lecția, care contează dincolo de bug:** `expo export` **trecea** înainte de corectură. Producea un fișier CSS de 0 octeți, fără nicio eroare. Am considerat asta „bundle verificat" de cinci ori la rând. Un build care trece nu înseamnă că funcționează — dovada e că token-urile definite doar în `global.css` (`#27272a`, `#8b5cf6`) **lipseau din bundle** înainte și apar după.

**Cum recunoști un server Metro vechi.** `postcss.config.js` e citit o singură dată, la pornirea worker-ilor de transformare. Dacă schimbi configurația sau dependențele în timp ce `expo start` rulează, procesul continuă cu starea veche și dă exact eroarea de mai sus. Semnul sigur în terminal:

```
Warning: Unknown at rule: @theme (global.css:6:7)
```

`@theme` ajuns crud la lightningcss înseamnă că PostCSS nu a rulat. Oprește serverul și pornește-l cu `npx expo start -c`. Un server curat nu produce niciodată acest avertisment.

Dacă vezi stiluri lipsă după o schimbare de dependențe, verifică întâi dacă token-urile din `global.css` ajung efectiv în bundle:

```bash
curl -s "http://127.0.0.1:8081/.expo/.virtual-metro-entry.bundle?platform=ios&dev=true" | grep -c "#27272a"
```

Zero înseamnă că CSS-ul nu a ajuns în aplicație, oricât de verde ar fi build-ul. Aceasta e calea pe care o cere chiar Expo Go — `expo export` ocolește serverul de dezvoltare și ascunde exact acest tip de eșec.

## 6. ⚠️ Versiunea web a aplicației mobile nu pornește

`npx expo export --platform web` construiește bundle-ul, dar la rulare cade în modulul `Animated` al lui **react-native-web**, care nu-și rezolvă propriile importuri cu această combinație de versiuni (Expo SDK 57 / RN 0.86 / react-native-web 0.21).

**Nu ne afectează produsul.** Aplicația țintește iOS și Android; web-ul îl foloseam doar ca verificare rapidă în browser. Verificarea vizuală se face pe telefon, cu Expo Go.

Ce funcționează pe web rămâne neatins: landing-ul Faza 0 și, mai târziu, paginile publice `@slug` — acelea sunt Laravel + Inertia, nu React Native.

**Un efect util al investigației:** `expo-secure-store` nu există pe web și strica bundle-ul la import. Acum stocarea tokenului trece prin `src/api/storage.ts`, care alege Keychain/Keystore pe native și `localStorage` pe web, cu încărcare leneșă. E oricum forma corectă.

---

## 7. Porturi folosite

| Port | Ce |
|---|---|
| 8000 | API + landing (Laravel) |
| 3306 | MariaDB (XAMPP) |
| 8081 | Metro (Expo) |

Dacă 8000 e ocupat de alt proiect, pornește pe alt port și setează `EXPO_PUBLIC_API_URL`.
