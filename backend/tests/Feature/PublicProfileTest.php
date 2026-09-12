<?php

use App\Domain\People\Enums\FieldSource;
use App\Domain\People\Models\Person;
use App\Domain\Profiles\Models\ProfileSubmission;
use App\Domain\Profiles\Models\PublicProfile;
use App\Models\User;
use Database\Seeders\InterestSeeder;
use Database\Seeders\NameDaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(InterestSeeder::class);
    $this->seed(NameDaySeeder::class);

    $this->owner = User::factory()->create(['name' => 'Maxim', 'locale' => 'ro']);
    $this->profile = PublicProfile::create([
        'user_id'      => $this->owner->id,
        'slug'         => PublicProfile::generateSlug('Maxim'),
        'display_name' => 'Maxim',
        'locale'       => 'ro',
        'visibility'   => PublicProfile::DEFAULT_VISIBILITY,
    ]);
});

function submit(string $slug, array $overrides = []): array
{
    return array_merge([
        'display_name' => 'Gheorghe Rusu',
        'birthday'     => '23.04.1998',
        'interests'    => ['audio', 'car_care'],
        'consent'      => '1',
    ], $overrides);
}

it('genereaza slug-uri neghicibile', function () {
    // Fara sufix aleator, oricine ar putea enumera profilurile.
    $first = PublicProfile::generateSlug('Ion');
    $second = PublicProfile::generateSlug('Ion');

    expect($first)->not->toBe($second)
        ->and($first)->toStartWith('ion-')
        ->and(mb_strlen($first))->toBeGreaterThan(6);
});

it('afiseaza pagina publica fara cont', function () {
    $this->get("/@{$this->profile->slug}")
        ->assertOk()
        ->assertSee('Maxim', escape: false);
});

it('afiseaza pagina in limba vizitatorului', function (string $locale, string $expected) {
    $this->withHeader('Accept-Language', $locale)
        ->get("/@{$this->profile->slug}")
        ->assertOk()
        ->assertSee($expected, escape: false);
})->with([
    ['ro', 'Despre tine'],
    ['ru', 'О вас'],
    ['en', 'About you'],
]);

it('nu expune nimic despre proprietar in mod implicit', function () {
    // Pagina e o invitatie, nu o vitrina. Fiecare camp expus e o decizie.
    $this->owner->update(['birth_date' => '1990-10-12']);

    $this->withHeader('Accept-Language', 'ro')
        ->get("/@{$this->profile->slug}")
        ->assertOk()
        ->assertDontSee('octombrie', escape: false);
});

it('expune ziua de nastere doar daca proprietarul a ales', function () {
    $this->owner->update(['birth_date' => '1990-10-12']);
    $this->profile->update(['visibility' => ['birth_date' => 'public']]);

    $this->withHeader('Accept-Language', 'ro')
        ->get("/@{$this->profile->slug}")
        ->assertOk()
        ->assertSee('octombrie', escape: false);
});

it('nu indexeaza paginile publice in mod implicit', function () {
    $this->get("/@{$this->profile->slug}")
        ->assertSee('name="robots" content="noindex"', escape: false);
});

it('creeaza persoana din completare, cu incredere de la sursa', function () {
    $this->post("/@{$this->profile->slug}", submit($this->profile->slug))->assertRedirect();

    $person = Person::sole();

    expect($person->user_id)->toBe($this->owner->id)
        ->and($person->display_name)->toBe('Gheorghe Rusu')
        ->and($person->birth_date->format('Y-m-d'))->toBe('1998-04-23')
        // subject_provided bate orice import din agenda (docs/04 § 3).
        ->and($person->trustLevel('birth_date'))->toBe(FieldSource::SubjectProvided)
        ->and($person->interests->pluck('code')->sort()->values()->all())->toBe(['audio', 'car_care']);
});

it('creeaza si ocaziile, inclusiv onomastica', function () {
    $this->post("/@{$this->profile->slug}", submit($this->profile->slug));

    $types = Person::sole()->occasions->pluck('type')->sort()->values();

    expect($types->all())->toBe(['birthday', 'name_day']);
});

it('accepta o zi de nastere fara an', function () {
    $this->post("/@{$this->profile->slug}", submit($this->profile->slug, ['birthday' => '23.04']));

    $person = Person::sole();

    expect($person->birth_year_known)->toBeFalse()
        ->and($person->birth_date->format('m-d'))->toBe('04-23')
        ->and($person->age())->toBeNull();
});

it('cere consimtamant explicit', function () {
    $this->post("/@{$this->profile->slug}", submit($this->profile->slug, ['consent' => null]))
        ->assertSessionHasErrors('consent');

    expect(Person::count())->toBe(0);
});

it('versioneaza consimtamantul si nu stocheaza IP-ul in clar', function () {
    $this->post("/@{$this->profile->slug}", submit($this->profile->slug));

    $submission = ProfileSubmission::sole();

    expect($submission->consent_version)->not->toBeEmpty()
        ->and($submission->consented_at)->not->toBeNull()
        ->and($submission->ip_hash)->toHaveLength(64)
        ->and($submission->ip_hash)->not->toContain('127.0.0.1');
});

it('respinge coduri de interes inventate', function () {
    $this->post("/@{$this->profile->slug}", submit($this->profile->slug, ['interests' => ['inventat']]))
        ->assertSessionHasErrors('interests.0');
});

it('permite stergerea fara cont, dintr-un link', function () {
    // Cine a completat trebuie sa poata retrage la fel de usor cum a dat.
    $this->post("/@{$this->profile->slug}", submit($this->profile->slug));

    $token = ProfileSubmission::sole()->delete_token;

    $this->get(route('profile.destroy', ['slug' => $this->profile->slug, 'token' => $token]))->assertOk();

    expect(ProfileSubmission::count())->toBe(0)
        ->and(Person::count())->toBe(0);
});

it('nu sterge persoana daca proprietarul a modificat-o intre timp', function () {
    // I-am sterge propria munca.
    $this->post("/@{$this->profile->slug}", submit($this->profile->slug));

    $person = Person::sole();
    $this->actingAs($this->owner)->patchJson("/api/v1/people/{$person->id}", ['display_name' => 'Gicu ❤️']);

    $token = ProfileSubmission::sole()->delete_token;
    $this->get(route('profile.destroy', ['slug' => $this->profile->slug, 'token' => $token]))->assertOk();

    expect(Person::count())->toBe(1)
        ->and(Person::sole()->display_name)->toBe('Gicu ❤️');
});

it('actualizeaza persoana la o a doua completare, in loc sa o dubleze', function () {
    $this->post("/@{$this->profile->slug}", submit($this->profile->slug));
    $this->post("/@{$this->profile->slug}", submit($this->profile->slug, ['interests' => ['coffee']]));

    expect(Person::count())->toBe(1)
        ->and(Person::sole()->interests->pluck('code')->sort()->values()->all())
        ->toBe(['audio', 'car_care', 'coffee']);
});

it('arata pagina de link inactiv, nu o eroare', function () {
    $this->profile->update(['is_active' => false]);

    $this->get("/@{$this->profile->slug}")->assertOk()->assertSee('Wishio', escape: false);
});

it('limiteaza completarile de la acelasi IP', function () {
    foreach (range(1, 10) as $n) {
        $this->post("/@{$this->profile->slug}", submit($this->profile->slug, ['display_name' => "Persoana $n"]));
    }

    $this->post("/@{$this->profile->slug}", submit($this->profile->slug))->assertStatus(429);
});

it('creeaza profilul la prima cerere si intoarce linkul', function () {
    $user = User::factory()->create(['name' => 'Ana']);

    $response = $this->actingAs($user)->getJson('/api/v1/profile')->assertOk();

    expect($response->json('data.slug'))->toStartWith('ana-')
        ->and($response->json('data.url'))->toContain('/@ana-')
        ->and($response->json('data.visibility.birth_date'))->toBe('private');
});

it('salveaza vizibilitatea si ignora campurile necunoscute', function () {
    $this->actingAs($this->owner)->getJson('/api/v1/profile');

    $this->actingAs($this->owner)
        ->patchJson('/api/v1/profile', ['visibility' => ['birth_date' => 'public', 'inventat' => 'public']])
        ->assertOk()
        ->assertJsonPath('data.visibility.birth_date', 'public')
        ->assertJsonMissingPath('data.visibility.inventat');
});

it('gestioneaza lista de dorinte', function () {
    $item = $this->actingAs($this->owner)
        ->postJson('/api/v1/wishlist', ['kind' => 'product', 'title' => 'Căști wireless', 'visibility' => 'public'])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($this->owner)->getJson('/api/v1/profile')->assertJsonCount(1, 'data.wishlist');

    $this->actingAs($this->owner)->deleteJson("/api/v1/wishlist/$item")->assertNoContent();
});

it('nu lasa un utilizator sa stearga din lista altuia', function () {
    $item = $this->actingAs($this->owner)
        ->postJson('/api/v1/wishlist', ['kind' => 'product', 'title' => 'Secret'])
        ->json('data.id');

    $this->actingAs(User::factory()->create())
        ->deleteJson("/api/v1/wishlist/$item")
        ->assertForbidden();
});
