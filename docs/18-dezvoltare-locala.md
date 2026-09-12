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

## 5. ⚠️ Versiunea web a aplicației mobile nu pornește

`npx expo export --platform web` construiește bundle-ul, dar la rulare cade în modulul `Animated` al lui **react-native-web**, care nu-și rezolvă propriile importuri cu această combinație de versiuni (Expo SDK 57 / RN 0.86 / react-native-web 0.21).

**Nu ne afectează produsul.** Aplicația țintește iOS și Android; web-ul îl foloseam doar ca verificare rapidă în browser. Verificarea vizuală se face pe telefon, cu Expo Go.

Ce funcționează pe web rămâne neatins: landing-ul Faza 0 și, mai târziu, paginile publice `@slug` — acelea sunt Laravel + Inertia, nu React Native.

**Un efect util al investigației:** `expo-secure-store` nu există pe web și strica bundle-ul la import. Acum stocarea tokenului trece prin `src/api/storage.ts`, care alege Keychain/Keystore pe native și `localStorage` pe web, cu încărcare leneșă. E oricum forma corectă.

---

## 6. Porturi folosite

| Port | Ce |
|---|---|
| 8000 | API + landing (Laravel) |
| 3306 | MariaDB (XAMPP) |
| 8081 | Metro (Expo) |

Dacă 8000 e ocupat de alt proiect, pornește pe alt port și setează `EXPO_PUBLIC_API_URL`.
