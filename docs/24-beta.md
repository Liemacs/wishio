# 24 — Beta: TestFlight și testarea internă pe Android

> S12.3. Două săptămâni cu 20–30 de oameni reali, pe aplicația din producție, înainte de trimiterea în review (S12.4). Scopul nu e să confirmăm că merge, ci să aflăm unde se împiedică oamenii — și cât de departe ajung în pâlnia din `docs/07 § 2`.

---

## 1. Ce trebuie să existe înainte

| Ce | Unde | Stare |
|---|---|---|
| Serverul de producție, cu HTTPS pe `wishio.md` | `docs/10 § 2` | **lipsește** |
| `WISHIO_PUSH_DRIVER=expo` în `.env` pe server | — | la deploy |
| Aplicația creată în App Store Connect, cu Bundle ID `md.wishio.app` | App Store Connect | **lipsește** |
| Aplicația creată în Play Console, pachetul `md.wishio.app` | Play Console | **lipsește** |
| Cheia APNs pentru push pe iOS | EAS o generează la primul build, dacă răspunzi „da” la notificări | la build |
| Push pe Android: `google-services.json` și cheia FCM V1 | `docs/18 § 5c` | **lipsește** — fără ea, pe Android nu ajunge niciun reminder |
| Contul demo pentru Beta App Review | `php artisan wishio:demo --force` pe server (`docs/23 § 5`) | la deploy |
| Linkurile `wishio.md/@slug` care deschid aplicația | `WISHIO_IOS_APP_ID` = `TEAMID.md.wishio.app`; `WISHIO_ANDROID_SHA256` = amprenta cheii de semnare din Play Console → App integrity (nu cea din EAS, dacă Play semnează aplicația) | după crearea aplicațiilor |

Din repo sunt deja gata: `usesNonExemptEncryption: false` în `app.json` (fără declarația de export la fiecare build), pista internă pe Android în `eas.json`, versiunea minimă obligatorie (S12.6) și măsurarea din § 6.

---

## 2. Build și distribuție

Aceleași build-uri merg în testare și, mai târziu, în review: profilul `production`.

```bash
cd mobile && npx expo-doctor
```

```bash
cd mobile && npx eas-cli@latest build --platform ios --profile production
```

```bash
cd mobile && npx eas-cli@latest submit --platform ios --latest
```

```bash
cd mobile && npx eas-cli@latest build --platform android --profile production
```

```bash
cd mobile && npx eas-cli@latest submit --platform android --latest
```

**iPhone — TestFlight.** Un grup extern („Beta Moldova”) cu link public: testerii instalează TestFlight și deschid linkul. Primul build al fiecărei versiuni trece prin Beta App Review, de obicei în una-două zile; build-urile următoare ale aceleiași versiuni ajung la testeri mai repede. Un build expiră după 90 de zile.

**Android — testare internă.** Până la 100 de testeri, după adresa contului Google, fără review. Google cere ca **primul** build să fie încărcat de mână în Play Console (fișierul `.aab` din pagina build-ului EAS); abia după aceea `eas submit` îl poate trimite singur, cu cheia contului de serviciu în `mobile/google-service-account.json` (nu intră în git). Play Console poate cere declarațiile de conținut — Data safety, clasificarea — înainte de primul release pe orice pistă; răspunsurile sunt în `docs/22`.

---

## 3. Textele pentru testeri

### TestFlight → Test Information

**Beta App Description**

- **ro:** Wishio îți amintește de zilele de naștere, onomasticile și sărbătorile cu cadouri ale oamenilor apropiați și îți sugerează cadouri din magazine din Moldova, în bugetul tău. Versiune de test, înainte de lansare.
- **ru:** Wishio напоминает о днях рождения, именинах и праздниках с подарками близких людей и предлагает подарки из магазинов Молдовы в вашем бюджете. Тестовая версия перед запуском.
- **en:** Wishio reminds you of the birthdays, name days and gift-giving holidays of the people close to you, and suggests gifts from shops in Moldova within your budget. Test version before launch.

Tot acolo: adresa pentru feedback, `https://wishio.md/legal/privacy` și, la Beta App Review, contul demo cu notele din `docs/23 § 5`.

### What to Test (la fiecare build)

- **ro:** Mulțumim că testezi Wishio! Folosește aplicația cu oamenii tăi reali și încearcă, în ordinea asta: 1) adaugă cel puțin 5 oameni, din agendă sau de mână; 2) confirmă onomasticile găsite; 3) pornește notificările; 4) cere idei de cadou pentru cineva care are ziua în curând; 5) trimite linkul tău personal unui prieten. Orice te încurcă ne ajută: fă o captură de ecran și trimite-o din TestFlight.
- **ru:** Спасибо, что тестируете Wishio! Пользуйтесь приложением с реальными людьми и попробуйте по порядку: 1) добавьте не меньше 5 человек — из контактов или вручную; 2) подтвердите найденные именины; 3) включите уведомления; 4) подберите идеи подарка для того, у кого скоро день рождения; 5) отправьте свою личную ссылку другу. Нам поможет всё, что вас запутало: сделайте скриншот и отправьте его из TestFlight.
- **en:** Thanks for testing Wishio! Use the app with your real people and try, in this order: 1) add at least 5 people, from contacts or by hand; 2) confirm the name days found; 3) turn on notifications; 4) get gift ideas for someone with a birthday coming up; 5) send your personal link to a friend. Anything that confuses you helps: take a screenshot and send it from TestFlight.

### Play Console → notele versiunii de testare

- **ro:** Versiune de test. Adaugă cel puțin 5 oameni, confirmă onomasticile, pornește notificările, cere idei de cadou și trimite-ți linkul unui prieten. Spune-ne orice te încurcă.
- **ru:** Тестовая версия. Добавьте не меньше 5 человек, подтвердите именины, включите уведомления, подберите идеи подарка и отправьте свою ссылку другу. Расскажите нам обо всём, что запутало.
- **en:** Test version. Add at least 5 people, confirm the name days, turn on notifications, get gift ideas and send your link to a friend. Tell us anything that confuses you.

---

## 4. Cine testează

**20–30 de oameni**, aleși cu grijă, nu doar prietenii apropiați — ei iartă prea mult:
- cel puțin 10 pe Android: în Moldova e platforma majoritară;
- cel puțin 8 vorbitori de rusă, câțiva pe calendarul pe stil vechi;
- oameni care fac des cadouri: părinți, colegi care organizează zilele de naștere, cei cu familii mari.

Testerii sunt utilizatori reali, pe serverul de producție: politica de confidențialitate li se aplică la fel, iar la final își pot păstra sau șterge contul.

### Mesajul de recrutare

**ro**
> Am construit Wishio — o aplicație care îți amintește de zilele de naștere și onomasticile oamenilor apropiați și îți dă idei de cadou din magazine din Moldova, în bugetul tău.
>
> Caut 25 de oameni care s-o testeze două săptămâni înainte de lansare, pe iPhone sau Android, în română sau rusă. E gratuită. Îți cer doar s-o folosești cu oamenii tăi reali și să-mi spui sincer ce nu merge.
>
> Dacă vrei, scrie-mi în privat ce telefon ai. Pentru Android am nevoie și de adresa de Gmail, ca să te pot adăuga la test.

**ru**
> Wishio — это приложение, которое напоминает о днях рождения и именинах близких людей и подсказывает идеи подарков из магазинов Молдовы в вашем бюджете.
>
> Ищем 25 человек, которые протестируют его две недели до запуска, на iPhone или Android, на русском или румынском. Это бесплатно. Просим только пользоваться им с реальными людьми и честно рассказать, что не работает.
>
> Если хотите участвовать, напишите в личные сообщения, какой у вас телефон. Для Android понадобится ещё адрес Gmail, чтобы добавить вас в тест.

**en**
> Wishio is an app that reminds you of the birthdays and name days of the people close to you and suggests gift ideas from shops in Moldova, within your budget.
>
> We're looking for 25 people to test it for two weeks before launch, on iPhone or Android. It's free. We only ask you to use it with your real people and to tell us honestly what doesn't work.
>
> If you're in, send a private message with the phone you use. For Android we also need your Gmail address to add you to the test.

### Formularul de feedback

Pentru Android nu există capturile din TestFlight, deci un formular scurt, pentru toți (Google Forms sau Tally), în română și rusă:

1. Ce telefon ai și în ce limbă folosești aplicația?
2. Ce ai încercat să faci și nu ți-a ieșit?
3. Onomasticile găsite au fost corecte? Dacă nu, pentru ce nume?
4. Ai primit un reminder? A venit la momentul potrivit?
5. Între ideile de cadou, ai găsit ceva ce ai cumpăra? (1–5)
6. Ai trimis linkul personal cuiva? Ce ți-a spus?
7. De la 0 la 10, cât de probabil e s-o recomanzi unui prieten? De ce?
8. Ce ai schimba primul?

---

## 5. Cele două săptămâni

| Când | Ce faci |
|---|---|
| Ziua 0 | build-ul în TestFlight și pe pista internă; invitațiile; mesajul de bun venit, cu pașii din „What to Test” și linkul formularului |
| Zilele 1–3 | în fiecare dimineață: `wishio:metrics`, joburile eșuate, logurile. Un blocaj în onboarding se repară imediat, cu build nou |
| Ziua 7 | un mesaj la jumătate, formularul; trei discuții de 15 minute cu testeri care s-au oprit devreme |
| Ziua 14 | formularul final, cifrele din § 6, lista de reparat înainte de S12.4 |

**La final decizi** între trimiterea în review (S12.4) și încă o rundă, după trei întrebări:
- A rămas vreun blocaj în onboarding, pe vreo platformă sau în vreo limbă?
- Ajung reminderele pe ambele platforme, la ora aleasă?
- Cât de departe ajung oamenii în pâlnie? Porțile G3–G5 sunt pentru după lansare (`PLAN.md`); în beta, cifrele arată ce reparăm întâi.

---

## 6. Ce măsurăm

```bash
cd backend && php artisan wishio:metrics --since=<ziua 0>
```

Pâlnia din `docs/07 § 2` pentru conturile create de la ziua 0 — conturile `@wishio.md` nu intră — și tabloul săptămânal din `docs/07 § 6`. Definițiile sunt în `docs/07 § 7`. Rata de deschidere a reminderelor (G4) se măsoară din S12.3: aplicația raportează apăsarea unei notificări.

**Înainte de fiecare build trimis testerilor:**
- `npx expo-doctor` trece;
- testele backend sunt verzi, iar `npm run i18n:check` și `npm run store:check` trec;
- pe telefon: rutele fără contacte, fără push și offline din `docs/18 § 5d`;
- un reminder real ajunge și, apăsat, deschide persoana;
- un link `wishio.md/@slug` deschide aplicația;
- o trecere prin toate trei limbile;
- ștergerea unui cont de test, din aplicație.
