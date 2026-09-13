# 10 — Operare: medii, release, testare, incidente

> Ce se întâmplă *după* ce codul e scris. Lipsea complet din plan.
> Scris pentru un fondator care operează singur — deci simplu și automatizat, nu „enterprise".

---

## 1. Medii

| Mediu | Rol | Bază de date | Cine îl atinge |
|---|---|---|---|
| **local** | dezvoltare | MariaDB (XAMPP) | tu |
| **staging** | test înainte de release, când va fi nevoie de el | MySQL 8.4, date fictive | tu |
| **production** | utilizatori reali și contul demo pentru App Review | MySQL 8, backup zilnic | deploy dintr-un tag `v*`, după teste (`docs/25 § 5`) |

**Regula:** date reale **niciodată** în staging. Contul demo pentru App Review trăiește în producție, cu date fictive (`php artisan wishio:demo`): build-ul trimis în review vorbește cu serverul de producție, deci doar acolo îl poate folosi reviewerul. Dacă vreodată copiezi producția în staging, anonimizează.

---

## 2. Infrastructură

| | |
|---|---|
| Hosting | VPS în UE (Hetzner CX22 sau similar) — **datele în UE simplifică Legea 195/2024** |
| Administrare | Forge sau Ploi: HTTPS, worker, planificare, backup, deploy (D-022, `docs/25`) |
| Web server | Nginx + PHP-FPM 8.3 |
| Bază de date | MySQL 8.4 pe aceeași mașină la început; separată când depășești ~5k utilizatori activi |
| Redis | pe aceeași mașină |
| Cozi | `queue:work` pe Redis, ca daemon în panou, repornit la deploy; Horizon când va fi nevoie de panoul lui (D-022) |
| Cron | `php artisan schedule:run` la fiecare minut |
| TLS | Let's Encrypt, reînnoire automată |
| Fișiere | S3-compatibil în UE (Hetzner Object Storage): deocamdată doar backup-urile, aplicația nu primește fișiere încărcate |

**Nu Kubernetes, nu microservicii, nu multi-region.** Un VPS duce lejer 25.000 de utilizatori pentru acest profil de trafic. Complexitatea prematură omoară proiectele solo.

---

## 3. CI/CD

### Pe fiecare pull request
```
lint          → Pint (PHP), tsc --noEmit (TS)
analiză       → PHPStan nivel 6
teste         → php artisan test (pe MySQL 8.4 în container)
i18n          → verifică paritatea cheilor ro/ru/en; lipsă = build roșu
eval AI       → setul de 30 de profiluri (doar când se schimbă prompturile)
```

### La merge în `main`
```
teste    → aceleași verificări ca la pull request, plus textele din store
backend  → niciun deploy: staging nu există încă
```

### Release în producție
```
tag v0.x.y → teste în CI → linkul de deploy al panoului → deploy/deploy.sh:
             dependențe, migrări, cache, restart worker, verificare /up
mobile     → EAS build profil `production` → eas submit (TestFlight, pista internă) → review
```

**Zero-downtime:** panourile oferă de obicei deploy în director nou + symlink; merită pornit când traficul o cere. Până atunci, deploy-ul pe loc durează câteva secunde.
**Rollback:** deploy al commitului anterior. Migrările **nu** se rollback-uiesc automat — de aceea vezi §4.

---

## 4. Migrări — regula care previne dezastrele

**Fiecare migrare trebuie să fie compatibilă înainte și înapoi cu versiunea anterioară a codului.** Motivul e mobile: utilizatorii rămân pe versiuni vechi de aplicație săptămâni întregi. Backend-ul trebuie să le servească în continuare.

Deci ștergerea unei coloane se face în **trei release-uri**:
1. codul nu mai scrie în ea
2. codul nu mai citește din ea
3. abia apoi `dropColumn`

Niciodată într-unul singur.

---

## 5. Release-uri mobile

### Profiluri EAS

| Profil | Pentru | Distribuție |
|---|---|---|
| `development` | dev client cu module native | internă |
| `preview` | testeri, fiecare merge în main | internal / TestFlight |
| `production` | store | App Store / Play |

### OTA updates (`expo-updates`)
Poți livra schimbări **doar de JavaScript** fără review de store. Foarte util pentru corecturi de copy și bugfixuri.

**Reguli:**
- OTA doar pentru corecții și texte, **niciodată** pentru funcționalitate nouă (regulile Apple)
- OTA nu poate livra cod nativ — un modul nou cere build complet
- canal separat per profil; nu trimite niciodată OTA de development în producție

### Versionare
`major.minor.patch` + `buildNumber`/`versionCode` auto-incrementat de EAS.
Backend-ul expune `/api/v1/app-config` cu `min_supported_version` → dacă aplicația e prea veche, ecran de update forțat. Fără el, ești blocat pe vecie să susții orice versiune lansată vreodată.

**Implementat în S12.6.** Versiunea minimă vine din `WISHIO_MIN_APP_VERSION` și se compară cu `version` din `mobile/app.json`. O ridici doar când o schimbare de API strică versiunile vechi și abia după ce versiunea nouă e aprobată în ambele magazine — altfel oamenii sunt trimiși într-un store care încă n-o are. Linkurile spre magazine vin din `WISHIO_APP_STORE_URL` (după crearea aplicației în App Store Connect) și `WISHIO_PLAY_STORE_URL`. Fără răspuns de la server, aplicația merge mai departe.

---

## 6. Strategia de testare

Nu urmărim acoperire procentuală. Urmărim **regulile care, dacă se rup, distrug produsul**.

### Obligatoriu testat (cele 10 reguli din CLAUDE.md)

| Ce | Tip | De ce |
|---|---|---|
| Agenda nu ajunge pe server; doar hash-uri | feature + inspecție payload | regula 4 — respingere App Store dacă se rupe |
| Nicio dată a unei persoane nu trece la alt user | feature, cu 2 utilizatori | regula 3 — problemă legală |
| Override manual nu e suprascris de sync | unit | regula 7 — bug tăcut, imposibil de observat |
| Ierarhia de trust respectă ordinea | unit | regula 7 |
| Normalizare telefon E.164 | unit | `069123456` = `+37369123456` = `00373...` |
| Potrivire nume fără diacritice | unit | `Ștefan` = `stefan` = `Штефан` |
| Recomandările conțin doar produse existente | feature | regula 2 — halucinație = **bug critic** |
| Anti-repetare din gift_history | feature | promisiunea produsului |
| Quiet hours și limitele anti-spam | unit | regula 8 |
| Aplicația merge fără Contacts și fără push | E2E manual | regula 5, la **fiecare** release |
| Paritatea cheilor ro/ru/en | script în CI | regula 1 |
| Fallback la RO pentru limbă nesuportată | feature | verificat deja pe `/api/v1/ping` |

### Piramida
- **Unit** — logica de domeniu: date, trust, normalizare, scoring. Rapide, multe.
- **Feature (HTTP)** — fiecare endpoint, inclusiv autorizarea. **Fiecare endpoint are un test care verifică că userul B nu vede datele userului A.**
- **E2E manual** — un checklist de 20 de pași înainte de fiecare submit, pe device fizic, în toate trei limbile.
- **Eval AI** — setul de 30 de profiluri, în CI.

### Ce NU testăm automat
UI-ul mobile (fragil, scump). În schimb: checklist manual pe device fizic + screenshot-uri în 3 limbi la fiecare release.

---

## 7. Monitorizare și alerte

| Semnal | Unealtă | Prag de alarmă |
|---|---|---|
| Erori aplicație | Sentry | orice eroare nouă în producție |
| Crash rate mobile | Sentry | > 1% sesiuni |
| Uptime API | UptimeRobot | 2 verificări eșuate |
| Adâncime cozi | Horizon | > 100 joburi în așteptare |
| Joburi eșuate | Horizon | orice eșec la notificări |
| Livrare push | log propriu | < 90% |
| Cost AI zilnic | contor propriu | > pragul din `config/wishio.php` |
| Recomandări cu 0 rezultate | PostHog | > 10% |
| **Halucinații prinse de validare** | log | **> 0 — alertă imediată** |
| Spațiu pe disc | monitor VPS | > 80% |

**Scrubbing PII în Sentry configurat explicit**, nu presupus. Verifică-l cu un eveniment de test care conține un nume.

---

## 8. Backup și recuperare

| | |
|---|---|
| Ce | dump MySQL complet + fișiere încărcate |
| Cum | backup-ul bazei din panou (`docs/25 § 7`) |
| Cât de des | zilnic 03:00, plus înainte de fiecare migrare în producție |
| Unde | object storage în UE, **criptat**, separat de serverul aplicației |
| Retenție | 7 zilnice + 4 săptămânale + 3 lunare |
| **Test de restaurare** | **lunar, pe staging.** Un backup netestat nu e backup |
| RPO / RTO | maximum 24 h date pierdute / 4 h până la revenire |

---

## 9. Incidente — pentru un om singur

Nu ai on-call. Deci ai nevoie de un *runbook*, nu de un proces.

**Severitate 1** (aplicația e jos, sau se scurg date): oprește tot, remediază, comunică în aplicație și prin email. Dacă sunt date personale expuse, **Legea 195/2024 cere notificarea autorității în 72 de ore** — vezi `docs/06`.

**Severitate 2** (o funcție e ruptă — de ex. nu pleacă notificările): dezactivează funcția printr-un feature flag, remediază în aceeași zi.

**Severitate 3** (bug cosmetic): intră în backlog.

**Feature flags în PostHog din S1.** Capacitatea de a stinge o funcție fără release de store este singura pârghie reală pe care o ai când ceva se strică pe mobile.

---

## 10. Rutina săptămânală (30 de minute)

1. Sentry — erori noi
2. Horizon — joburi eșuate
3. Tabloul de 5 cifre din `docs/07 § 6`
4. Costul AI al săptămânii
5. Recenzii și feedback din store
6. Un backup restaurat, dacă e prima luni a lunii
