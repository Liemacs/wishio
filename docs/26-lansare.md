# 26 — Lansarea

> S12.5. Cum ajunge Wishio la primii 300 de oameni în săptămâna lansării (`docs/11 § 6`): momentul, site-ul, sursele măsurate și textele gata, în română, rusă și engleză. Ce ține de cod e făcut; restul e muncă de oameni.

---

## 1. Când

Lansarea se leagă de un vârf de cadouri, cu 3–6 săptămâni înainte (`docs/11 § 7`). Drumul până acolo: domeniul și serverul (`docs/25`), două săptămâni de beta (`docs/24`), trimiterea în review, cu buffer de două săptămâni (S12.4).

| Vârf | Data | Ce înseamnă |
|---|---|---|
| Sf. Mihail și Gavriil | 8 noiembrie (21 pe stil vechi) | prea devreme pentru lansare; bun pentru beta: primele onomastici reale |
| **Sf. Nicolae** | **6 decembrie** (19 pe stil vechi) | **ținta**: lansarea în jurul datei de 16 noiembrie, cu trei săptămâni înainte. Testul direct al onomasticilor (`docs/11 § 7`) |
| Crăciunul și Anul Nou | 25 decembrie – 7 ianuarie | al doilea val, cu cel mai mare volum |
| Sf. Vasile, Sf. Ion | 1 și 7 ianuarie (14 și 20 pe stil vechi) | onomastici foarte răspândite |
| 8 Martie | 8 martie | **rezerva**, dacă noiembrie nu iese: totul gata până la jumătatea lui ianuarie |

---

## 2. Ce trebuie să fie gata

| Ce | Unde | Stare |
|---|---|---|
| Aplicația aprobată în App Store și Google Play | S12.4 | după beta |
| `WISHIO_APP_STORE_URL` și `WISHIO_PLAY_STORE_URL` pe server | `.env` | la aprobare; până atunci site-ul spune „în curând” |
| Pagina principală, care prezintă aplicația | `WISHIO_LANDING=app`, implicit | gata |
| Butoanele spre magazine după completarea unui link personal | `docs/09`, W3 | gata; apar odată cu linkurile de mai sus |
| Bannerul App Store în Safari, pe `wishio.md` | se activează singur, din id-ul aplicației | gata |
| Previzualizarea linkului în Telegram, Facebook, Viber | `backend/public/og/app-*.png` | gata; verifică-l trimițându-ți linkul |
| Insignele oficiale App Store și Google Play | de pe site-urile Apple și Google, cu regulile lor de folosire | opțional: azi butoanele sunt text, fără logo-uri |
| Adresa de ajutor, citită zilnic | `docs/25 § 1` | la domeniu |

---

## 3. Sursele

Fiecare link distribuit poartă sursa lui: `wishio.md/?src=fb_mame`, `?src=telegram_chisinau`, `?src=reddit`, `?src=presa_newsmaker`. Litere mici, cifre, `-` și `_`.

Site-ul numără, pe zi și pe sursă, vizitele pe pagina principală și apăsările spre magazine — fără IP, fără cookie, fără nimic ce ar lega două vizite de același om. Apăsările de pe pagina de după completarea unui link personal apar ca `profile_link`.

```bash
cd backend && php artisan wishio:metrics --since=2026-11-16
```

Raportul are pâlnia conturilor noi, cele cinci cifre ale săptămânii și sursele. Instalările nu se pot lega de sursă — aplicația nu are analytics —, deci sursele arată interesul: vizite și apăsări spre magazin. Instalările, pe țări și pe proveniență, le arată separat App Store Connect și Play Console.

---

## 4. Săptămâna lansării

| Ziua | Ce |
|---|---|
| Z−3 | versiunea aprobată e în magazine, linkurile sunt setate, testerii din beta primesc mesajul de mulțumire (§ 5) |
| Z0, dimineața | postarea personală — Facebook, Instagram, LinkedIn; cercului apropiat îi ceri instalarea **și** trimiterea linkului personal |
| Z0–Z2 | câte un grup de Facebook pe zi: părinți, mame, IT, cumpărături, comunitățile orașelor; fiecare cu sursa lui |
| Z1 | Telegram: canale și chaturi locale unde anunțurile sunt permise |
| Z2 | Reddit r/moldova |
| Z3–Z5 | comunicatul de presă, cu unghiul „aplicație din Moldova care știe onomasticile” (`docs/11 § 6`) |
| Z7 | bilanțul: `wishio:metrics`, recenziile din magazine, feedbackul primit |
| Z14–Z20 | al doilea val, legat de Sf. Nicolae (§ 5) |

**Nu:** reclamă plătită înainte ca retenția D30 să treacă de 25% (`docs/11 § 6`); aceeași postare în zeci de grupuri în aceeași zi; mesaje trimise în numele utilizatorilor (regula 10).

**Dacă ceva se strică:** runbook-ul din `docs/10 § 9`. O schimbare de API care rupe versiunile vechi se acoperă cu versiunea minimă obligatorie (S12.6). Aplicația nu are actualizări OTA, deci o reparație în aplicație cere build nou și review — de aceea săptămâna lansării nu se suprapune cu alt release.

---

## 5. Textele

### Postarea personală

**ro**
> Wishio, aplicația la care lucrez de câteva luni, e acum în App Store și Google Play.
>
> E ușor să uiți o zi de naștere — și încă mai ușor o onomastică. Wishio le ține minte pentru tine, îți amintește cu câteva zile înainte și îți dă idei de cadou din magazine din Moldova, în bugetul tău. Onomasticile le găsește singur, din prenume.
>
> E gratuită, în română, rusă și engleză. Nu citește numere de telefon și nu arată nimănui lista ta.
>
> wishio.md/?src=personal
>
> Dacă o încerci, trimite-le prietenilor linkul tău personal din aplicație: își scriu singuri ziua, iar tu nu mai trebuie să întrebi.

**ru**
> Wishio — приложение, над которым я работаю последние месяцы, — теперь в App Store и Google Play.
>
> День рождения забыть легко, а именины — ещё легче. Wishio помнит их за вас, напоминает за несколько дней и подсказывает идеи подарков из магазинов Молдовы в вашем бюджете. Именины находит сам, по имени.
>
> Бесплатно, на русском, румынском и английском. Приложение не читает номера телефонов и никому не показывает ваш список.
>
> wishio.md/?src=personal
>
> Если попробуете, отправьте друзьям свою личную ссылку из приложения: они сами укажут свой день рождения.

**en**
> Wishio, the app I've been working on for the past few months, is now on the App Store and Google Play.
>
> It's easy to forget a birthday, and even easier to forget a name day. Wishio remembers them for you, reminds you a few days ahead and suggests gift ideas from shops in Moldova, within your budget. It finds name days on its own, from first names.
>
> It's free, in Romanian, Russian and English. It never reads phone numbers and never shows your list to anyone.
>
> wishio.md/?src=personal

### Grupurile de Facebook

**ro**
> Pentru cine uită zilele de naștere și onomasticile: Wishio le ține minte, îți amintește din timp și îți dă idei de cadou din magazine din Moldova, în bugetul tău. Gratuită, în română și rusă. wishio.md/?src=fb_<grup> — orice părere ne ajută, citim tot.

**ru**
> Для тех, кто забывает дни рождения и именины: Wishio помнит их, напоминает заранее и подсказывает идеи подарков из магазинов Молдовы в вашем бюджете. Бесплатно, на русском и румынском. wishio.md/?src=fb_<группа> — любой отзыв поможет, мы читаем всё.

### Telegram

**ro**
> 🎂 Wishio ține minte zilele de naștere și onomasticile oamenilor apropiați și îți dă idei de cadou din magazine din Moldova. Gratuit, în română, rusă și engleză. wishio.md/?src=telegram_<canal>

**ru**
> 🎂 Wishio помнит дни рождения и именины близких и подсказывает идеи подарков из магазинов Молдовы. Бесплатно, на русском, румынском и английском. wishio.md/?src=telegram_<канал>

### Reddit r/moldova

**Titlu (en):** I built a free app that remembers birthdays and name days, and suggests gifts from shops in Moldova

**Titlu (ro):** Am făcut o aplicație gratuită care ține minte zilele de naștere și onomasticile și sugerează cadouri din magazine din Moldova

> Hi r/moldova! Name days matter here as much as birthdays, and no global app knows them. So Wishio works them out from first names, on the new or the old calendar, and you just confirm. It reminds you a few days ahead and suggests gifts from shops in Moldova, within your budget.
>
> On privacy: it never reads phone numbers from your contacts. Only the names and birthdays of the people you pick reach the server — photos stay on your phone — and nobody else sees your list. You can delete your account from the app.
>
> It's free, in Romanian, Russian and English: wishio.md/?src=reddit. Honest feedback is very welcome, especially on name days that come out wrong.

### Comunicatul de presă

**ro**
> **Wishio, o aplicație din Moldova care ține minte zilele de naștere și onomasticile și sugerează cadouri din magazine locale**
>
> Chișinău, [data] — Wishio, o aplicație gratuită pentru iPhone și Android, se lansează în Republica Moldova. Aplicația amintește de zilele de naștere, onomasticile și sărbătorile cu cadouri ale oamenilor apropiați și sugerează cadouri din magazine din Moldova, în bugetul ales.
>
> Aplicațiile globale de calendar nu știu nimic despre onomastici, deși în Moldova contează la fel de mult ca zilele de naștere. Wishio deduce onomastica din prenume, după calendarul pe stil nou sau vechi, iar utilizatorul doar o confirmă. Pentru că ziua de naștere lipsește adesea din agenda telefonului, fiecare utilizator are și un link personal pe care îl trimite prietenilor: aceștia își completează singuri ziua și ce le place.
>
> [Citat: o frază despre de ce ai construit aplicația.]
>
> Aplicația a fost construită cu atenție la datele personale: nu citește numere de telefon, emailuri sau poze din agendă, nu arată nimănui lista utilizatorului, iar contul se șterge direct din aplicație.
>
> Wishio e disponibilă în română, rusă și engleză, în App Store, în Google Play și pe wishio.md.
>
> **Contact:** [nume, email, telefon]

**ru**
> **Wishio — молдавское приложение, которое помнит дни рождения и именины и подсказывает подарки из местных магазинов**
>
> Кишинёв, [дата] — В Молдове запускается Wishio, бесплатное приложение для iPhone и Android. Оно напоминает о днях рождения, именинах и праздниках с подарками близких людей и подсказывает подарки из магазинов Молдовы в выбранном бюджете.
>
> Глобальные приложения-календари ничего не знают об именинах, хотя в Молдове они не менее важны, чем дни рождения. Wishio определяет именины по имени, по новому или старому стилю, а пользователю остаётся только подтвердить. Поскольку даты рождения в телефонной книге часто нет, у каждого пользователя есть личная ссылка: друзья сами указывают по ней свой день рождения и что им нравится.
>
> [Цитата: одна фраза о том, зачем создано приложение.]
>
> Приложение бережно относится к личным данным: не читает номера телефонов, почту и фотографии из контактов, никому не показывает список пользователя, а аккаунт удаляется прямо в приложении.
>
> Wishio доступен на румынском, русском и английском языках в App Store, Google Play и на wishio.md.
>
> **Контакт:** [имя, email, телефон]

**Unde:** portalurile de știri generale (de exemplu NewsMaker, Point.md, Agora.md, Diez.md, Ziarul de Gardă), rubricile de tehnologie și comunitățile de startup-uri (Moldova IT Park, Tekwill), emisiunile de dimineață TV și radio. Contactele redacțiilor se verifică înainte; comunicatul pleacă personalizat, nu în copie la toți.

### Al doilea val — Sf. Nicolae

**ro**
> Pe 6 decembrie e Sf. Nicolae. Câți Nicolae ai în agendă? Wishio îi găsește singur, din prenume, și îți amintește din timp — iar pentru cine ține stilul vechi, pe 19 decembrie. wishio.md/?src=sf_nicolae

**ru**
> 6 декабря — день святого Николая, а по старому стилю — 19 декабря. Сколько Николаев в ваших контактах? Wishio найдёт их сам, по имени, и напомнит заранее. wishio.md/?src=sf_nicolae

### Mesajul pentru testerii din beta (Z−3)

**ro**
> Mulțumim că ați testat Wishio! Aplicația e acum în App Store și Google Play, iar contul vostru rămâne același. O rugăminte: dacă v-a fost de folos, trimiteți linkul vostru personal din aplicație câtorva prieteni.

**ru**
> Спасибо, что тестировали Wishio! Приложение уже в App Store и Google Play, а аккаунт остаётся тем же. Одна просьба: если Wishio пригодился, отправьте свою личную ссылку из приложения нескольким друзьям.
