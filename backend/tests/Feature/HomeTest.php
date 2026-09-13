<?php

use App\Domain\Metrics\Actions\BuildMetricsReport;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'wishio.landing'               => 'app',
        'wishio.app.store_url.ios'     => null,
        'wishio.app.store_url.android' => null,
    ]);
});

it('prezinta aplicatia pe pagina principala, in toate cele trei limbi', function (string $locale, string $title) {
    $this->withHeader('Accept-Language', $locale)->get('/')
        ->assertOk()
        ->assertSee($title, escape: false)
        ->assertSee("/og/app-$locale.png", escape: false)
        // Pagina aplicației se poate găsi în motoarele de căutare; restul site-ului, nu.
        ->assertDontSee('name="robots" content="noindex"', escape: false);
})->with([
    ['ro', 'Nu uiți nicio ocazie. Știi ce să oferi.'],
    ['ru', 'Ни одного забытого праздника. И понятно, что подарить.'],
    ['en', 'Never miss an occasion. Always know what to give.'],
]);

it('spune ca aplicatia vine in curand cat timp nu e in magazine', function () {
    $this->withHeader('Accept-Language', 'ro')->get('/')
        ->assertSee('În curând pe iPhone și Android', escape: false)
        ->assertDontSee('/app/ios', escape: false)
        ->assertDontSee('apple-itunes-app', escape: false);
});

it('arata butoanele doar spre magazinele publicate si pastreaza sursa vizitei', function () {
    config(['wishio.app.store_url.ios' => 'https://apps.apple.com/app/id1234567890']);

    $this->withHeader('Accept-Language', 'ro')->get('/?src=fb_mame')
        ->assertSee(route('app.download', ['platform' => 'ios', 'src' => 'fb_mame']), escape: false)
        ->assertDontSee('/app/android', escape: false)
        ->assertSee('name="apple-itunes-app" content="app-id=1234567890', escape: false);
});

it('numara vizitele si clickurile spre magazine pe sursa, fara sa identifice pe nimeni', function () {
    config(['wishio.app.store_url.android' => 'https://play.google.com/store/apps/details?id=md.wishio.app']);

    $this->get('/?src=Telegram');
    $this->get('/?src=telegram');
    $this->get('/');
    $this->get('/app/android?src=telegram')
        ->assertRedirect('https://play.google.com/store/apps/details?id=md.wishio.app');

    $rows = DB::table('channel_stats')->get();

    expect((int) $rows->where('source', 'telegram')->firstWhere('event', 'view')->total)->toBe(2)
        ->and((int) $rows->where('source', '')->firstWhere('event', 'view')->total)->toBe(1)
        ->and((int) $rows->where('source', 'telegram')->firstWhere('event', 'android')->total)->toBe(1)
        ->and(array_keys((array) $rows->first()))->toBe(['id', 'day', 'source', 'event', 'total', 'created_at', 'updated_at']);
});

it('trimite spre pagina principala daca aplicatia nu e inca in magazin', function () {
    $this->get('/app/ios')->assertRedirect(route('landing'));

    expect(DB::table('channel_stats')->where('event', 'ios')->exists())->toBeFalse();
});

it('nu primeste cererile din Faza 0 cand site-ul prezinta aplicatia', function () {
    $this->post('/cerere', ['relationship' => 'friend'])->assertNotFound();
});

it('trimite la pagina de ajutor din subsol', function () {
    $this->withHeader('Accept-Language', 'ro')->get('/')
        ->assertSee(route('legal', ['key' => 'support', 'lang' => 'ro']), escape: false);
});

it('arata in raport de unde au venit vizitatorii', function () {
    $this->get('/?src=reddit');
    $this->get('/?src=reddit');

    expect(app(BuildMetricsReport::class)->channels(CarbonImmutable::now()))
        ->toBe([['source' => 'reddit', 'views' => 2, 'ios' => 0, 'android' => 0]]);

    $this->artisan('wishio:metrics')->expectsOutputToContain('reddit')->assertSuccessful();
});
