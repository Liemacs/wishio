<?php

namespace App\Console\Commands;

use App\Domain\Account\Actions\DeleteAccount;
use App\Domain\Catalog\Models\Product;
use App\Domain\Occasions\Actions\SyncHolidayOccasions;
use App\Domain\Occasions\Actions\SyncOccasionsForPerson;
use App\Domain\Occasions\Models\Holiday;
use App\Domain\Occasions\Models\Occasion;
use App\Domain\People\Actions\MarkGiftGiven;
use App\Domain\People\Actions\SaveGiftIdea;
use App\Domain\People\Actions\WritePersonField;
use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\GiftIdea;
use App\Domain\People\Models\Interest;
use App\Domain\People\Models\Person;
use App\Domain\Profiles\Actions\ReceiveSubmission;
use App\Domain\Profiles\Models\ProfileSubmission;
use App\Domain\Profiles\Models\PublicProfile;
use App\Domain\Recommendations\Actions\GenerateRecommendations;
use App\Domain\Reminders\Actions\ScheduleReminders;
use App\Domain\Reminders\Models\UserSettings;
use App\Models\User;
use App\Support\Names\NameNormalizer;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Contul demo pentru App Review și pentru capturile din store (PLAN S12.2).
 *
 * Datele trec prin aceleași acțiuni ca în aplicație — proveniență, onomastici
 * deduse din prenume, idei, istoric, recomandări, o completare „cine este?” —
 * ca cine deschide contul să vadă ce vede un om după câteva săptămâni de folosit.
 *
 * Zilele de naștere se calculează față de ziua rulării: rulată înainte de
 * fiecare trimitere în review, comanda lasă mereu o ocazie în zilele următoare.
 */
class DemoAccountCommand extends Command
{
    protected $signature = 'wishio:demo
                            {--email=review@wishio.md : Adresa contului demo}
                            {--locale=en : Limba contului și a datelor din el: ro, ru sau en}
                            {--password= : Parola; altfel WISHIO_DEMO_PASSWORD sau una generată}
                            {--force : Reface contul dacă există, fără confirmare}';

    protected $description = 'Creează sau reface contul demo pentru App Review și capturile din store';

    /** Versiunea textului de consimțământ de pe formularul public. */
    private const CONSENT_VERSION = '2026-09-1';

    /**
     * Oamenii din cont. `birthday`: peste câte zile e ziua de naștere; `age`:
     * vârsta pe care o împlinește atunci. Fără zi de naștere rămâne onomastica.
     */
    private const PEOPLE = [
        ['ro' => 'Ana Rusu', 'ru' => 'Анна Руссу', 'relationship' => 'sibling', 'gender' => 'f', 'birthday' => 3, 'age' => 27, 'interests' => ['books_fiction', 'coffee', 'yoga'], 'budget' => [300, 1000]],
        ['ro' => 'Mihai Ceban', 'ru' => 'Михаил Чебан', 'relationship' => 'friend', 'gender' => 'm', 'birthday' => 10, 'age' => 31, 'interests' => ['audio', 'pc_gaming', 'coffee_gear'], 'budget' => [500, 6000]],
        ['ro' => 'Elena Popescu', 'ru' => 'Елена Попеску', 'relationship' => 'parent', 'gender' => 'f', 'birthday' => 24, 'age' => 58, 'interests' => ['home_decor', 'gardening', 'tea'], 'budget' => [300, 1500]],
        ['ro' => 'Ion Munteanu', 'ru' => 'Иван Мунтяну', 'relationship' => 'parent', 'gender' => 'm', 'birthday' => 45, 'age' => 61, 'interests' => ['wine', 'car_care', 'fishing'], 'budget' => [500, 2000]],
        ['ro' => 'Maria Ciobanu', 'ru' => 'Мария Чобану', 'relationship' => 'colleague', 'gender' => 'f', 'birthday' => 70, 'age' => 35, 'interests' => ['coffee', 'board_games', 'plants'], 'budget' => [200, 800]],
        ['ro' => 'Alexandru Rotaru', 'ru' => 'Александр Ротару', 'relationship' => 'sibling', 'gender' => 'm', 'birthday' => 101, 'age' => 24, 'interests' => ['console_gaming', 'running'], 'budget' => [500, 3000]],
        ['ro' => 'Natalia Lungu', 'ru' => 'Наталья Лунгу', 'relationship' => 'partner', 'gender' => 'f', 'birthday' => 150, 'age' => 29, 'interests' => ['fragrance_women', 'jewelry', 'spa_products'], 'budget' => [1000, 5000]],
        ['ro' => 'Andrei Bivol', 'ru' => 'Андрей Бивол', 'relationship' => 'child', 'gender' => 'm', 'birthday' => 200, 'age' => 9, 'interests' => ['construction_toys', 'educational_toys'], 'budget' => [300, 1500]],
        ['ro' => 'Nicolae Guțu', 'ru' => 'Николай Гуцу', 'relationship' => 'other', 'gender' => 'm', 'birthday' => null, 'age' => null, 'interests' => ['wine', 'gardening'], 'budget' => [300, 1000]],
        ['ro' => 'Sofia Rusu', 'ru' => 'София Руссу', 'relationship' => 'other', 'gender' => 'f', 'birthday' => 260, 'age' => 6, 'interests' => ['toys', 'art_supplies'], 'budget' => [200, 800]],
    ];

    /** Textele scrise de „utilizator”, în limba contului. */
    private const COPY = [
        'ro' => [
            'owner'           => 'Andreea Moraru',
            'note'            => 'Citește romane polițiste și bea cafeaua fără zahăr.',
            'idea'            => 'Card cadou la cafeneaua ei preferată',
            'wish_product'    => 'Căști wireless pentru alergat',
            'wish_experience' => 'Un atelier de olărit',
            'wish_place'      => 'O cină la Orheiul Vechi',
            'message'         => 'Mulțumesc că mă ții minte! 🎉',
        ],
        'ru' => [
            'owner'           => 'Анастасия Морару',
            'note'            => 'Читает детективы и пьёт кофе без сахара.',
            'idea'            => 'Подарочная карта в её любимую кофейню',
            'wish_product'    => 'Беспроводные наушники для бега',
            'wish_experience' => 'Мастер-класс по гончарному делу',
            'wish_place'      => 'Ужин в Старом Орхее',
            'message'         => 'Спасибо, что помнишь! 🎉',
        ],
        'en' => [
            'owner'           => 'Andreea Moraru',
            'note'            => 'Reads crime novels and takes her coffee without sugar.',
            'idea'            => 'Gift card for her favorite coffee shop',
            'wish_product'    => 'Wireless running earbuds',
            'wish_experience' => 'A pottery workshop',
            'wish_place'      => 'Dinner at Orheiul Vechi',
            'message'         => 'Thanks for remembering me! 🎉',
        ],
    ];

    public function handle(DeleteAccount $deleteAccount): int
    {
        $email = mb_strtolower(trim((string) $this->option('email')));
        $locale = (string) $this->option('locale');

        if (! isset(self::COPY[$locale]) || ! in_array($locale, config('wishio.locales.supported'), true)) {
            $this->components->error("Limba [{$locale}] nu e suportată. Alege ro, ru sau en.");

            return self::FAILURE;
        }

        if ($missing = $this->missingBaseData()) {
            $this->components->error('Lipsesc datele de bază ('.implode(', ', $missing).'). Rulează întâi NameDaySeeder, InterestSeeder, HolidaySeeder și CatalogSeeder.');

            return self::FAILURE;
        }

        if ($existing = User::where('email', $email)->first()) {
            if (! $this->option('force') && ! $this->confirm("Contul {$email} există. Îl ștergi și îl refaci cu date demo?")) {
                $this->components->warn('Nu am schimbat nimic.');

                return self::FAILURE;
            }

            $deleteAccount($existing);
        }

        $password = (string) ($this->option('password') ?: config('wishio.demo.password'));
        $generated = $password === '';

        if ($generated) {
            $password = Str::password(16, symbols: false);
        }

        $user = DB::transaction(fn () => $this->build($email, $locale, $password));

        $this->report($user, $password, $generated);

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function missingBaseData(): array
    {
        return array_keys(array_filter([
            'onomastici' => DB::table('name_days')->doesntExist(),
            'interese'   => Interest::query()->doesntExist(),
            'sărbători'  => Holiday::query()->doesntExist(),
            'catalog'    => Product::query()->recommendable()->doesntExist(),
        ]));
    }

    private function build(string $email, string $locale, string $password): User
    {
        $copy = self::COPY[$locale];
        $today = CarbonImmutable::now('Europe/Chisinau')->startOfDay();

        $user = User::create([
            'name'         => $copy['owner'],
            'email'        => $email,
            'password'     => $password,
            'locale'       => $locale,
            'country_code' => 'MD',
            'timezone'     => 'Europe/Chisinau',
            // Ca la înregistrare: vorbitorii de rusă din Moldova urmează în
            // general stilul vechi.
            'name_day_calendar' => $locale === 'ru' ? 'orthodox_old' : 'orthodox_new',
            'birth_date'        => $today->addDays(130)->subYears(30)->toDateString(),
        ]);

        UserSettings::create(['user_id' => $user->id]);

        $people = $this->people($user, $locale, $today);
        $people['Ana Rusu']->update(['notes' => $copy['note']]);

        // Onomasticile deduse, confirmate ca de proprietar: contul arată o
        // agendă folosită, nu una abia importată.
        Occasion::where('user_id', $user->id)->where('type', 'name_day')->update([
            'confirmed_at' => now(),
            'source'       => FieldSource::OwnerManual->value,
            'confidence'   => FieldSource::OwnerManual->defaultConfidence(),
        ]);

        $this->gifts($people, $copy, $today);
        $this->publicLink($user, $people['Ana Rusu'], $copy, $locale);

        app(SyncHolidayOccasions::class)($user);
        app(ScheduleReminders::class)($user);

        return $user;
    }

    /** @return Collection<string, Person> persoanele, după numele în română */
    private function people(User $user, string $locale, CarbonImmutable $today): Collection
    {
        $writeField = app(WritePersonField::class);
        $syncOccasions = app(SyncOccasionsForPerson::class);
        $normalizer = app(NameNormalizer::class);

        return collect(self::PEOPLE)->mapWithKeys(function (array $entry) use ($user, $locale, $today, $writeField, $syncOccasions, $normalizer) {
            $name = $locale === 'ru' ? $entry['ru'] : $entry['ro'];

            $person = $user->people()->create([
                'display_name'          => $name,
                'given_name_normalized' => $normalizer->firstName($name),
                'budget_min'            => $entry['budget'][0],
                'budget_max'            => $entry['budget'][1],
            ]);

            // Prin acțiune, nu direct: fiecare câmp primește proveniența lui.
            $writeField($person, 'display_name', $name, FieldSource::OwnerManual);
            $writeField($person, 'relationship', $entry['relationship'], FieldSource::OwnerManual);
            $writeField($person, 'gender', $entry['gender'], FieldSource::OwnerManual);

            if ($entry['birthday'] !== null) {
                $birthDate = $today->addDays($entry['birthday'])->subYears($entry['age'])->toDateString();
                $writeField($person, 'birth_date', $birthDate, FieldSource::OwnerManual);
                $writeField($person, 'birth_year_known', true, FieldSource::OwnerManual);
            }

            $person->interests()->syncWithoutDetaching(
                Interest::whereIn('code', $entry['interests'])->pluck('id')
                    ->mapWithKeys(fn (int $id) => [$id => [
                        'source'     => FieldSource::OwnerManual->value,
                        'confidence' => FieldSource::OwnerManual->defaultConfidence(),
                    ]])->all()
            );

            $syncOccasions($person);

            return [$entry['ro'] => $person];
        });
    }

    /**
     * Idei salvate, un cadou oferit anul trecut și o căutare de cadou gata
     * făcută: ce apare pe ecranul de idei și în „Ai cumpărat?”.
     *
     * @param  Collection<string, Person>  $people
     * @param  array<string, string>  $copy
     */
    private function gifts(Collection $people, array $copy, CarbonImmutable $today): void
    {
        $saveIdea = app(SaveGiftIdea::class);

        if ($headphones = $this->productFor('audio')) {
            $saveIdea($people['Mihai Ceban'], $headphones, null, 'chosen');
        }

        if ($perfume = $this->productFor('fragrance_women')) {
            $saveIdea($people['Natalia Lungu'], $perfume, null, 'idea');
        }

        $saveIdea($people['Ana Rusu'], null, $copy['idea'], 'idea');

        // Recomandările nu mai propun un cadou deja oferit.
        if ($wine = $this->productFor('wine')) {
            app(MarkGiftGiven::class)($saveIdea($people['Ion Munteanu'], $wine, null, 'purchased'), $today->year - 1, 'birthday');
        }

        $mihai = $people['Mihai Ceban'];

        app(GenerateRecommendations::class)(
            $mihai->fresh(),
            500,
            6000,
            $mihai->occasions()->where('type', 'birthday')->first(),
        );
    }

    private function productFor(string $interest): ?Product
    {
        return Product::query()
            ->recommendable()
            ->whereHas('interests', fn ($query) => $query->where('code', $interest))
            ->orderByDesc('gift_score')
            ->first();
    }

    /**
     * Linkul public, cu listă de dorințe, și o completare care așteaptă
     * răspunsul la „cine este?”: Ana își trimite singură ziua, iar numele ei
     * seamănă cu un contact existent, deci decide proprietarul.
     *
     * @param  array<string, string>  $copy
     */
    private function publicLink(User $user, Person $ana, array $copy, string $locale): void
    {
        $profile = PublicProfile::create([
            'user_id'      => $user->id,
            'slug'         => PublicProfile::generateSlug($user->name),
            'display_name' => $user->name,
            'locale'       => $locale,
            'visibility'   => ['birth_date' => 'public', 'interests' => 'private', 'wishlist' => 'public'],
        ]);

        foreach ([['product', 'wish_product', 'want'], ['experience', 'wish_experience', 'maybe'], ['place', 'wish_place', 'want']] as [$kind, $key, $priority]) {
            $user->wishlistItems()->create([
                'kind'       => $kind,
                'title'      => $copy[$key],
                'priority'   => $priority,
                'visibility' => 'public',
            ]);
        }

        $submission = ProfileSubmission::create([
            'public_profile_id' => $profile->id,
            'display_name'      => $ana->display_name,
            'birth_date'        => $ana->birth_date,
            'birth_year_known'  => true,
            'interest_codes'    => ['books_fiction', 'coffee'],
            'message'           => $copy['message'],
            'locale'            => $locale,
            'consent_version'   => self::CONSENT_VERSION,
            'consented_at'      => now(),
            'delete_token'      => Str::random(48),
        ]);

        app(ReceiveSubmission::class)($submission);
    }

    private function report(User $user, string $password, bool $generated): void
    {
        $profile = $user->publicProfile()->first();

        $this->components->info('Contul demo e gata.');

        $this->table([], [
            ['Email', $user->email],
            ['Parolă', $password],
            ['Limbă', $user->locale],
            ['Persoane', $user->people()->count()],
            ['Ocazii', Occasion::where('user_id', $user->id)->count()],
            ['Idei de cadou', GiftIdea::where('user_id', $user->id)->count()],
            ['Completări care așteaptă', $profile->submissions()->whereNull('accepted_at')->count()],
            ['Link public', url('/@'.$profile->slug)],
        ]);

        if ($generated) {
            $this->components->warn('Parola a fost generată acum și nu se mai afișează: treci-o în App Store Connect → App Review.');
        }
    }
}
