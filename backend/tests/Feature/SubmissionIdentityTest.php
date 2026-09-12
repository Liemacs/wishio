<?php

use App\Domain\People\Actions\ImportContacts;
use App\Domain\People\Actions\WritePersonField;
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

    $this->owner = User::factory()->create(['locale' => 'ro']);
    $this->profile = PublicProfile::create([
        'user_id'      => $this->owner->id, 'slug' => PublicProfile::generateSlug('Maxim'),
        'display_name' => 'Maxim', 'locale' => 'ro', 'visibility' => PublicProfile::DEFAULT_VISIBILITY,
    ]);

    // Doua contacte „Ana” in agenda, ca in criteriul din PLAN.md S9.8.
    app(ImportContacts::class)($this->owner, [
        ['device_contact_id' => 'agenda-ana-rusu', 'display_name' => 'Ana Rusu', 'birth_date' => '1990-01-10', 'birth_year_known' => true],
        ['device_contact_id' => 'agenda-ana-ciobanu', 'display_name' => 'Ana Ciobanu'],
    ]);

    $this->anaRusu = Person::where('display_name', 'Ana Rusu')->sole();
    $this->anaCiobanu = Person::where('display_name', 'Ana Ciobanu')->sole();
});

function fillLink(PublicProfile $profile, array $overrides = []): void
{
    test()->post("/@{$profile->slug}", array_merge([
        'display_name' => 'Ana Popescu', 'birthday' => '05.05.1995',
        'interests'    => ['coffee'], 'consent' => '1',
    ], $overrides))->assertRedirect();
}

function resolveAs(User $user, ProfileSubmission $submission, ?int $personId)
{
    return test()->actingAs($user)
        ->postJson("/api/v1/submissions/{$submission->id}/resolve", ['person_id' => $personId]);
}

it('nu atinge niciun contact pana cand proprietarul alege', function () {
    fillLink($this->profile);

    expect(Person::count())->toBe(2)
        ->and(ProfileSubmission::sole()->accepted_at)->toBeNull()
        ->and($this->anaRusu->fresh()->birth_date->format('Y-m-d'))->toBe('1990-01-10')
        ->and($this->anaCiobanu->fresh()->birth_date)->toBeNull()
        ->and($this->anaCiobanu->fresh()->interests)->toBeEmpty();
});

it('arata completarea si doar contactele proprietarului care seamana', function () {
    // Un alt utilizator are si el o „Ana”: nu apare niciodata (docs/00 § D-021).
    Person::create(['user_id' => User::factory()->create()->id, 'display_name' => 'Ana Straina']);

    fillLink($this->profile);

    $this->actingAs($this->owner)->getJson('/api/v1/submissions/pending')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.display_name', 'Ana Popescu')
        ->assertJsonPath('data.0.interests.0.code', 'coffee')
        ->assertJsonCount(2, 'data.0.candidates')
        ->assertJsonMissing(['display_name' => 'Ana Straina']);
});

it('aduce datele in contactul ales, fara duplicat', function () {
    fillLink($this->profile);

    resolveAs($this->owner, ProfileSubmission::sole(), $this->anaCiobanu->id)
        ->assertOk()
        ->assertJsonPath('data.person_id', $this->anaCiobanu->id);

    $ana = $this->anaCiobanu->fresh();

    expect(Person::count())->toBe(2)
        // Numele ramane cel sub care o stie proprietarul.
        ->and($ana->display_name)->toBe('Ana Ciobanu')
        ->and($ana->birth_date->format('Y-m-d'))->toBe('1995-05-05')
        ->and($ana->trustLevel('birth_date'))->toBe(FieldSource::SubjectProvided)
        ->and($ana->interests->pluck('code')->all())->toBe(['coffee'])
        ->and($ana->occasions()->where('type', 'birthday')->exists())->toBeTrue()
        ->and($this->anaRusu->fresh()->birth_date->format('Y-m-d'))->toBe('1990-01-10');
});

it('creeaza o persoana noua cand proprietarul spune ca e altcineva', function () {
    fillLink($this->profile);

    resolveAs($this->owner, ProfileSubmission::sole(), null)->assertOk();

    expect(Person::count())->toBe(3)
        ->and(Person::where('display_name', 'Ana Popescu')->sole()->from_public_link)->toBeTrue();
});

it('lasa editarile manuale ale proprietarului sa castige', function () {
    app(WritePersonField::class)($this->anaRusu, 'birth_date', '1990-01-11', FieldSource::OwnerManual);

    fillLink($this->profile);
    resolveAs($this->owner, ProfileSubmission::sole(), $this->anaRusu->id)->assertOk();

    expect($this->anaRusu->fresh()->birth_date->format('Y-m-d'))->toBe('1990-01-11');
});

it('nu lasa pe altcineva sa raspunda si nici sa aleaga contactele altuia', function () {
    fillLink($this->profile);
    $submission = ProfileSubmission::sole();
    $stranger = User::factory()->create();
    $foreign = Person::create(['user_id' => $stranger->id, 'display_name' => 'Ana Straina']);

    resolveAs($stranger, $submission, null)->assertForbidden();
    resolveAs($this->owner, $submission, $foreign->id)->assertForbidden();

    expect($submission->fresh()->accepted_at)->toBeNull()
        ->and($foreign->fresh()->birth_date)->toBeNull();
});

it('nu dubleaza nimic la o a doua apasare', function () {
    fillLink($this->profile);
    $submission = ProfileSubmission::sole();

    resolveAs($this->owner, $submission, null)->assertOk();
    resolveAs($this->owner, $submission, null)->assertOk();

    expect(Person::count())->toBe(3);
});

it('leaga direct urmatoarea completare a aceluiasi om de contactul confirmat', function () {
    fillLink($this->profile);
    resolveAs($this->owner, ProfileSubmission::sole(), $this->anaCiobanu->id);

    fillLink($this->profile, ['birthday' => '06.05.1995']);

    expect(ProfileSubmission::pending()->count())->toBe(0)
        ->and(Person::count())->toBe(2)
        ->and($this->anaCiobanu->fresh()->birth_date->format('Y-m-d'))->toBe('1995-05-06');
});

it('pune o singura intrebare pentru doua completari ale aceluiasi om', function () {
    fillLink($this->profile);
    fillLink($this->profile, ['birthday' => '06.05.1995']);

    expect(ProfileSubmission::pending()->count())->toBe(2);

    resolveAs($this->owner, ProfileSubmission::orderBy('id')->first(), $this->anaRusu->id)->assertOk();

    expect(ProfileSubmission::pending()->count())->toBe(0)
        ->and(ProfileSubmission::pluck('person_id')->unique()->values()->all())->toBe([$this->anaRusu->id])
        // Ultima completare castiga: ziua corectata.
        ->and($this->anaRusu->fresh()->birth_date->format('Y-m-d'))->toBe('1995-05-06');
});

it('retragerea unei completari care asteapta nu atinge niciun contact', function () {
    fillLink($this->profile);

    $this->get(route('profile.destroy', ['slug' => $this->profile->slug, 'token' => ProfileSubmission::sole()->delete_token]))
        ->assertOk();

    expect(ProfileSubmission::count())->toBe(0)
        ->and(Person::count())->toBe(2);
});

it('trimite imediat o completare al carei nume nu seamana cu nimeni', function () {
    fillLink($this->profile, ['display_name' => 'Gheorghe Rusu']);

    expect(ProfileSubmission::pending()->count())->toBe(0)
        ->and(Person::count())->toBe(3);
});

it('recunoaste si contactele salvate cu numele de familie inainte', function () {
    app(ImportContacts::class)($this->owner, [['device_contact_id' => 'agenda-rusu-ion', 'display_name' => 'Rusu Ion']]);

    fillLink($this->profile, ['display_name' => 'Ion Rusu']);

    $this->actingAs($this->owner)->getJson('/api/v1/submissions/pending')
        ->assertJsonCount(1, 'data.0.candidates')
        ->assertJsonPath('data.0.candidates.0.display_name', 'Rusu Ion');
});
