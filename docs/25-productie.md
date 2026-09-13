# 25 — Producția: domeniu, server, deploy

> Pașii de la zero până la primul deploy, pentru decizia D-022: un VPS în UE, administrat cu Forge sau Ploi. Din repo sunt gata șablonul `.env`, scriptul de deploy și pornirea deploy-ului din CI. Restul se face o singură dată, în conturile tale.

---

## 0. Ordinea

| # | Pas | Depinde de |
|---|---|---|
| 1 | Domeniul `wishio.md` | verificarea numelui ca marcă (P0.4) |
| 2 | Serverul și panoul | — |
| 3 | Site-ul, baza de date, HTTPS | 1, 2 |
| 4 | Primul deploy, datele de bază, contul demo | 3 |
| 5 | Deploy-urile următoare, din tag | 4 |
| 6 | Notificări, linkuri, email | 4 și aplicațiile create în magazine |
| 7 | Backup și monitorizare | 4 |

---

## 1. Domeniul

**Înainte de cumpărare:** numele, verificat ca marcă la AGEPI (P0.4 din `PLAN.md`). Un domeniu pe un nume care nu se poate înregistra ca marcă e un risc pe care nu merită să-l iei după lansare.

Domeniul `wishio.md` se cumpără de la un registrar care vinde domenii `.md`. Înregistrările DNS, după ce există serverul:

| Tip | Nume | Valoare |
|---|---|---|
| A | `@` | IPv4-ul serverului |
| AAAA | `@` | IPv6-ul serverului, dacă îl are |
| CNAME | `www` | `wishio.md` |
| MX | `@` | de la furnizorul de email pentru domeniu |
| TXT | `@` | SPF, de la furnizorul SMTP |
| CNAME sau TXT | cum cere furnizorul SMTP | DKIM |
| TXT | `_dmarc` | `v=DMARC1; p=none` la început, apoi `quarantine`, după ce emailurile ajung |

**Adresele necesare:** `salut@wishio.md`, expeditorul rezumatului (`MAIL_FROM_ADDRESS`), și `privacy@wishio.md`, contactul din politică și din paginile de ajutor (`WISHIO_PRIVACY_EMAIL`). Un furnizor pentru primire și unul SMTP pentru trimitere, de preferat cu servere în UE. Amândoi intră în registru (`docs/20 § 2`).

---

## 2. Serverul și panoul

- **Serverul:** un VPS în UE — de exemplu Hetzner Cloud CX22, cu 2 vCPU și 4 GB RAM —, în Germania sau Finlanda, cu Ubuntu 24.04 LTS. Datele în UE simplifică Legea 195/2024 (`docs/10 § 2`).
- **Panoul:** Forge sau Ploi, legat de server. Serviciile: PHP 8.3, MySQL 8 (8.4 dacă panoul îl oferă; colația folosită există și în 8.0), Redis, Node LTS.
- **Panoul e împuternicit.** Are acces de administrator la server, deci la date: intră în registru, cu DPA. Forge ține de Laravel, din SUA; Ploi e o firmă din Țările de Jos. Locația contează pentru transferuri — verifică la contractare.
- **Autentificare în doi pași** pe Hetzner, pe panou, pe GitHub și la registrar (`docs/06 § 6`).

---

## 3. Site-ul

În panou, un site nou:

| Setare | Valoare |
|---|---|
| Domeniu | `wishio.md`, cu `www.wishio.md` ca alias |
| Tip | Laravel |
| Repo | `github.com/Liemacs/wishio`, ramura `main` |
| Directorul web | `/backend/public` — repo-ul e un monorepo, aplicația stă în `backend/` |
| Deploy automat la push | **oprit**: producția se actualizează doar din tag (§ 5) |
| Baza de date | `wishio`, cu un utilizator al ei |
| HTTPS | Let's Encrypt, după ce DNS-ul arată spre server |

`.env`-ul site-ului pornește de la `backend/.env.production.example` și se completează în editorul panoului. Cheia aplicației se generează local, iar valoarea se copiază în `APP_KEY`:

```bash
cd backend && php artisan key:generate --show
```

---

## 4. Primul deploy

**Scriptul de deploy din panou.** Păstrează liniile panoului pentru `git pull` și pentru reîncărcarea PHP-FPM, iar între ele pune:

```bash
bash deploy/deploy.sh
```

Scriptul intră în `backend/`, leagă `.env`-ul din rădăcina site-ului, instalează dependențele fără cele de dezvoltare, construiește CSS-ul paginilor publice, rulează migrările, pune configurația în cache, repornește worker-ul și verifică `/up`. Dacă panoul folosește alt binar PHP sau alt composer, le primește prin `PHP_BIN` și `COMPOSER_BIN`.

**Worker-ul și planificarea**, tot în panou:

| Ce | Comanda | Directorul |
|---|---|---|
| Daemon, pentru cozi | `php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600` | `<site>/backend` |
| Scheduler, în fiecare minut | `php artisan schedule:run` | `<site>/backend` |

Dacă panoul nu permite alegerea directorului, comanda primește calea completă spre `backend/artisan`.

**Datele de bază, o singură dată:**

```bash
cd backend && php artisan db:seed --class=NameDaySeeder --force && php artisan db:seed --class=InterestSeeder --force && php artisan db:seed --class=HolidaySeeder --force && php artisan db:seed --class=CatalogSeeder --force
```

Nu `php artisan db:seed` simplu: `DatabaseSeeder` creează și un utilizator de test cu o fabrică de date, iar Faker nu e instalat în producție.

**Contul demo**, cu parola din `WISHIO_DEMO_PASSWORD`:

```bash
cd backend && php artisan wishio:demo --force
```

**Verificarea:** `https://wishio.md/up`, `https://wishio.md/api/v1/ping`, `https://wishio.md/api/v1/app-config` și `https://wishio.md/legal/privacy` răspund.

---

## 5. Deploy-urile următoare

Producția se actualizează dintr-un tag pe ultimul commit din `main`:

```bash
git tag v0.1.1
```

```bash
git push origin v0.1.1
```

CI rulează testele backend și mobile și, dacă trec, cheamă linkul de deploy al panoului. Configurarea, o singură dată: linkul de deploy din panou se salvează în GitHub → Settings → Secrets and variables → Actions, ca `DEPLOY_HOOK_URL`. Fără el, jobul de deploy trece fără să facă nimic.

Panoul face deploy la ce e pe `main` în momentul acela. De aceea tag-ul se pune pe ultimul commit din `main`.

Înainte de un tag cu migrări, un backup manual din panou (§ 7). Migrările rămân compatibile cu versiunea anterioară a codului (`docs/10 § 4`), așa că o revenire înseamnă deploy-ul commitului anterior, nu anularea migrărilor.

---

## 6. Notificări, linkuri, email

| Ce | Cum |
|---|---|
| Notificările pleacă de pe server | `WISHIO_PUSH_DRIVER=expo` în `.env` |
| Push pe iOS | cheia APNs: EAS o creează la primul build `production`, dacă răspunzi „da” la notificări |
| Push pe Android | `google-services.json` în `mobile/` și cheia FCM V1 încărcată cu `npx eas-cli@latest credentials` (`docs/18 § 5c`) |
| `WISHIO_IOS_APP_ID` | `TEAMID.md.wishio.app`, cu Team ID din contul Apple Developer → Membership |
| `WISHIO_ANDROID_SHA256` | amprenta SHA-256 a cheii de semnare din Play Console → App integrity |
| `WISHIO_APP_STORE_URL`, `WISHIO_PLAY_STORE_URL` | după crearea aplicațiilor în magazine |
| Linkurile `wishio.md/@slug` | `https://wishio.md/.well-known/apple-app-site-association` și `/.well-known/assetlinks.json` răspund cu JSON |
| Emailul | `MAIL_*` în `.env`; un email de test trimis din `php artisan tinker` ajunge în căsuța ta |

---

## 7. Backup și monitorizare

- **Backup-ul bazei**, din panou, zilnic la 03:00, spre un object storage în UE (de exemplu Hetzner Object Storage), cu criptarea pornită. Păstrarea: ideal 7 zilnice, 4 săptămânale și 3 lunare (`docs/10 § 8`), cât permite panoul, dar cel puțin ultimele 7 zile. Plus un backup manual înainte de fiecare tag cu migrări.
- **Restaurarea, o dată pe lună:** un backup pus într-o bază separată și verificat — testul real din S11.7.
- **Uptime:** o verificare externă (de exemplu UptimeRobot) pe `https://wishio.md/up`, cu alertă după două eșecuri.
- **Săptămânal:** `php artisan queue:failed` și logurile din `backend/storage/logs`. Joburile eșuate se șterg singure după 7 zile (`docs/21`, M-11), logurile aplicației după 14. Retenția logurilor Nginx o setează panoul: verifică să fie tot 14 zile (M-14).
- **Sentry** rămâne pentru S11.7.

Furnizorii noi — serverul, panoul, emailul, stocarea pentru backup — intră în registru (`docs/20 § 2`) înainte de lansare.
