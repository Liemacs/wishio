# 23 — Materialele pentru store și App Review

> S12.1 și S12.2. Ce se încarcă în App Store Connect și în Play Console stă în repo, în română, rusă și engleză, generat sau verificat de scripturi. Ce nu se poate face din repo e în § 6.

---

## 1. Pe scurt

| Ce | Unde | Stare |
|---|---|---|
| Textele App Store: titlu, subtitlu, text promoțional, descriere, cuvinte-cheie, linkuri | `mobile/store.config.json` | gata; se încarcă cu `eas metadata:push` |
| Textele Google Play: titlu, descriere scurtă, descriere | `mobile/store/google-play/<limbă>/` | gata; se copiază în Play Console |
| Iconița aplicației, Android adaptiv, splash, favicon | `mobile/assets/`, generate din `mobile/store/brand/bow.svg` | gata — prima versiune |
| Iconița și bannerul din Google Play | `mobile/store/graphics/` | gata |
| Capturile de ecran | `mobile/store/screenshots/` | **lipsesc capturile din telefon**; încadrarea e automată |
| Pagina de ajutor (Support URL, obligatoriu în App Store) | `https://wishio.md/legal/support` | gata; lipsește adresa reală de contact (A7) |
| Pagina de ștergere a contului (obligatorie în Google Play) | `https://wishio.md/legal/delete-account` | gata; idem |
| Contul demo pentru App Review | `php artisan wishio:demo` | gata |
| Notele pentru App Review, în engleză | § 5 | gata |
| Versiunea minimă obligatorie | `GET /api/v1/app-config`, `WISHIO_MIN_APP_VERSION` (`docs/10 § 5`) | gata; `WISHIO_APP_STORE_URL` se setează după crearea aplicației în App Store Connect |

---

## 2. Textele

Sursele: `mobile/store.config.json` (App Store, în formatul EAS Metadata) și `mobile/store/google-play/` (Play). Descrierea e aceeași în ambele. Limitele și potrivirea dintre cele două le verifică:

```bash
cd mobile && npm run store:check
```

| Câmp | Limită | ro | ru | en |
|---|---|---|---|---|
| Titlu | 30 de caractere | 25 | 27 | 25 |
| Subtitlu (App Store) | 30 de caractere | 29 | 22 | 28 |
| Text promoțional (App Store) | 170 de caractere | 146 | 134 | 138 |
| Descriere | 4 000 de caractere | 1 478 | 1 484 | 1 456 |
| Cuvinte-cheie (App Store) | 100 de **octeți** | 97 | 91 | 71 |
| Descriere scurtă (Play) | 80 de caractere | 75 | 74 | 74 |

**Cum sunt scrise:**
- **Doar ce face aplicația azi.** Fără AI (niciun furnizor activ), fără widget, fără aniversări introduse de mână. Apple respinge metadatele care promit funcții inexistente (Guideline 2.3.1).
- Cuvintele-cheie nu repetă titlul și subtitlul, care sunt deja indexate. În română sunt fără diacritice, ca să încapă mai multe; în rusă fiecare literă ocupă doi octeți.
- Magazinele din catalog nu apar pe nume: numele altor companii sunt interzise în cuvintele-cheie.

**Încărcarea în App Store Connect** (cere aplicația creată acolo și autentificarea cu contul Apple Developer):

```bash
cd mobile && npx eas-cli@latest metadata:push
```

Nu sunt în fișier și se completează manual: categoria (Lifestyle, secundar Shopping), copyright-ul (după A7), chestionarul de vârstă, prețul (gratuit), țările (Moldova; România mai târziu) și datele contului demo (§ 5).

---

## 3. Grafica

Forma vine dintr-un singur fișier, `mobile/store/brand/bow.svg`: fundița albă, pe degradeul roz al aplicației (`#fb7185` → `#e11d48`). Restul se generează:

```bash
cd mobile && node store/render.mjs icons
```

| Fișier | Dimensiune | Pentru |
|---|---|---|
| `assets/icon.png` | 1024 × 1024, fără transparență | iOS; colțurile le rotunjește sistemul |
| `assets/android-icon-foreground.png`, `assets/android-icon-background.png` | 512 × 512 | iconița adaptivă Android |
| `assets/android-icon-monochrome.png` | 432 × 432 | iconițele tematice Android și iconița notificărilor |
| `assets/splash-icon.png` | 1024 × 1024, colțuri rotunjite | ecranul de pornire, pe alb |
| `assets/favicon.png` | 48 × 48 | build-ul web |
| `store/graphics/play-icon.png` | 512 × 512 | Google Play |
| `store/graphics/play-feature-<limbă>.png` | 1024 × 500, fără transparență | bannerul din Google Play, cu promisiunea din `docs/01 § 1` |

Scriptul folosește Google Chrome și ImageMagick (`magick`), instalate local. E o primă versiune făcută fără designer (`docs/08`, A2): dacă forma se schimbă, se schimbă doar `bow.svg` și se rulează din nou. Iconițele noi apar în aplicație abia după următorul build EAS.

---

## 4. Capturile de ecran

Șase capturi, aceleași în toate limbile. Titlurile, în RO/RU/EN, stau în `mobile/store/screenshots/shots.json`.

| # | Fișier | Ecranul | Titlul (ro) |
|---|---|---|---|
| 1 | `01-home.png` | ecranul principal, cu ocaziile care urmează | Nu mai uiți nicio ocazie |
| 2 | `02-person.png` | fișa unei persoane: zi de naștere, onomastică, interese | Onomasticile se găsesc singure |
| 3 | `03-gifts.png` | recomandările pentru Mihai, cu bugetul ales | Idei de cadou în bugetul tău |
| 4 | `04-ideas.png` | ideile salvate și istoricul cadourilor | Ține minte ce ai oferit |
| 5 | `05-link.png` | Profilul meu, cu linkul de trimis prietenilor | Prietenii își scriu singuri ziua |
| 6 | `06-reminders.png` | setările de notificări, cu rigla orei | Reminderul vine când vrei tu |

**Pașii:**

1. Câte un cont demo pentru fiecare limbă, pe backend-ul la care e conectat build-ul de dezvoltare (`docs/18 § 5c`):
   ```bash
   cd backend && php artisan wishio:demo --locale=ro --email=demo-ro@wishio.md --password=<parola>
   ```
   La fel cu `--locale=ru` și `--locale=en`. În rusă numele sunt chirilice, iar onomasticile urmează stilul vechi.
2. Pe iPhone: intri în contul limbii respective, pornești „Nu deranja”, apoi faci capturile obișnuite (butonul lateral + volum sus). Limba aplicației urmează contul.
3. Capturile se pun în `mobile/store/screenshots/raw/<ro|ru|en-US>/`, cu numele din tabel.
4. Încadrarea:
   ```bash
   cd mobile && node store/render.mjs screenshots
   ```
   Ies în `mobile/store/screenshots/out/`: `app-store-6.9/` (1320 × 2868 — App Store le scalează și pentru ecranele mai mici) și `google-play/` (1080 × 1920, 9:16). Ambele fără transparență, cum cer magazinele.

Capturile din telefon și cele încadrate nu intră în git: ocupă mult și se refac în câteva minute.

---

## 5. App Review

### Contul demo

```bash
cd backend && php artisan wishio:demo --force --password=<parola>
```

Creează `review@wishio.md`, în engleză: zece oameni cu zile de naștere și onomastici, idei de cadou, un cadou oferit anul trecut, o căutare de cadou gata făcută, un link public cu listă de dorințe și o completare care așteaptă „cine este?”. Datele trec prin aceleași acțiuni ca în aplicație.

Zilele de naștere se calculează față de ziua rulării. De aceea comanda **se rulează pe serverul de producție înainte de fiecare trimitere în review**, ca reviewerul să găsească ocazii în zilele următoare. Parola nu stă în repo: vine din `--password` sau din `WISHIO_DEMO_PASSWORD`; altfel se generează și se afișează o singură dată.

### Notele pentru reviewer

În App Store Connect → App Review Information → Notes, lângă datele contului demo:

```text
Wishio helps people in Moldova remember birthdays, name days and gift-giving holidays, and suggests gifts from local shops within a budget. The app is available in Romanian, Russian and English (My profile → Account → Language).

DEMO ACCOUNT
The demo account already has people, occasions, saved gift ideas, a gift history, a public link with a wishlist, and one submission from the public link waiting for the owner to confirm who sent it.

CONTACTS
Access to contacts is requested only on the import screen, after an explanation. The app reads only names and birthdays, and saves only the contacts the user selects. Phone numbers, emails and photos are never read. The app works fully without this permission: people can be added by hand (People → Add person).

NOTIFICATIONS
Reminders arrive a few days before each occasion, at the time set in My profile → Notifications. The permission is optional.

GIFT SUGGESTIONS AND AI
Suggestions come only from our catalog of products from shops in Moldova; tapping a product opens the shop's page. Before the first suggestion, the app asks whether it may send pseudonymized context (age range, gender, relationship, occasion, budget and interests; never names, notes or any text written by the user) to an AI provider. No external AI provider is enabled in this version, so both answers give the same suggestions. The choice can be changed at any time in My profile → Account.

PERSONAL LINK
"Your link" in My profile is a web page the user shares manually. Friends can add their own birthday and interests there, with explicit consent, and can delete what they sent without an account. The app never sends messages on the user's behalf.

ACCOUNT DELETION
My profile → Account → Delete account. Deletion is permanent and happens entirely in the app.
```

### Ce verifică de obicei reviewerii

| Guideline | Unde e în aplicație |
|---|---|
| 5.1.1(v) — ștergerea contului | My profile → Account → Delete account |
| 5.1.1 — aplicația merge fără permisiuni | adăugare manuală; S11.8 |
| 5.1.2 — date despre terți | doar numele și ziua contactelor alese; nimic partajat între utilizatori (`docs/06 § 2`) |
| 5.1.2(i) — date trimise unui AI terț | foaia de acord, retragere din Cont (`docs/21`, M-02) |
| 4.8 — Sign in with Apple | nu e cerut: aplicația are doar cont cu email și parolă, fără login prin terți |
| 2.3.1 — metadate corecte | § 2 |

---

## 6. Ce nu se poate face din repo

| Ce | De ce blochează | Cine |
|---|---|---|
| Serverul de producție și domeniul `wishio.md` | App Review folosește aplicația reală: API-ul, contul demo, paginile de ajutor și politica | tu — `docs/10 § 2` |
| Entitatea juridică (A7) | copyright-ul, operatorul din politică, datele de contact din pagina de ajutor | tu |
| Adresele de email ale domeniului | paginile de ajutor și de ștergere trimit la ele | tu, după domeniu |
| Aplicația creată în App Store Connect și în Play Console | `eas metadata:push`, TestFlight, testarea internă | tu |
| Capturile din telefon | § 4 | tu, după build |
