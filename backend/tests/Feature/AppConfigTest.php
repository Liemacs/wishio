<?php

it('spune versiunea minima suportata si unde se actualizeaza, fara cont', function () {
    // Aplicația întreabă înainte de login: un ecran vechi n-ar ajunge nici până acolo.
    config([
        'wishio.app.min_supported_version' => '1.2.0',
        'wishio.app.store_url.ios'         => 'https://apps.apple.com/app/id1234567890',
    ]);

    $this->getJson('/api/v1/app-config')
        ->assertOk()
        ->assertJsonPath('data.min_supported_version', '1.2.0')
        ->assertJsonPath('data.store_url.ios', 'https://apps.apple.com/app/id1234567890')
        ->assertJsonPath('data.store_url.android', 'https://play.google.com/store/apps/details?id=md.wishio.app');
});

it('nu blocheaza versiunea din app.json cu valoarea implicita', function () {
    // O valoare implicită mai mare decât versiunea aplicației ar trimite toți
    // utilizatorii în store după un deploy fără variabila setată.
    $app = json_decode(file_get_contents(base_path('../mobile/app.json')), true);

    expect(version_compare(config('wishio.app.min_supported_version'), $app['expo']['version'], '<='))->toBeTrue();
});
