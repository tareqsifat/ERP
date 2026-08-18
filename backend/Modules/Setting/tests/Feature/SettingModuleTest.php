<?php

use Modules\Setting\App\Services\SettingService;

// PRD v1 §3.15/§4.13 — Currency/Notification/System/Company Settings.

test('any authenticated user can read settings, grouped by tab', function () {
    actingAsRole('Line Supervisor'); // no setting.manage grant
    SettingService::set('company.name', 'Vishesh Textiles', 'company');

    $response = $this->getJson('/api/v1/settings')->assertOk();

    expect($response->json('data.company.name'))->toBe('Vishesh Textiles');
});

test('setting.manage holders can bulk-update a settings group', function () {
    actingAsRole('Admin');

    $response = $this->putJson('/api/v1/settings', [
        'group' => 'currency',
        'values' => ['code' => 'USD', 'symbol' => '$'],
    ])->assertOk();

    expect($response->json('data.currency.code'))->toBe('USD')
        ->and($response->json('data.currency.symbol'))->toBe('$');

    expect(SettingService::get('currency.code'))->toBe('USD');
});

test('a user without setting.manage cannot update settings', function () {
    actingAsRole('Line Supervisor');

    $this->putJson('/api/v1/settings', [
        'group' => 'company',
        'values' => ['name' => 'Hacked Textiles'],
    ])->assertStatus(403);
});
