<?php

use App\Models\Clinic;
use App\Models\VerificationTemplateVersion;
use App\Support\VerificationTemplateVersionService;
use Database\Seeders\RoleSeeder;

test('registration screen can be rendered', function () {
    config()->set('prodental.public_registration', true);

    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register with the templates available at registration', function (array $availableForms) {
    config()->set('prodental.public_registration', true);
    $this->seed(RoleSeeder::class);
    if ($availableForms !== []) {
        $master = seedWorkflowMaster();
        if (! in_array('short_form', $availableForms, true)) {
            $master->update(['active_short_form' => false]);
        }
    }

    $provisioner = Mockery::mock(VerificationTemplateVersionService::class)->makePartial();
    $provisioner->shouldReceive('provisionRegisteredClinic')
        ->once()->with(Mockery::type(Clinic::class), false)->passthru();
    $this->instance(VerificationTemplateVersionService::class, $provisioner);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '5551001234',
        'organization_name' => 'Test Dental Group',
        'clinic_name' => 'Test Dental Clinic',
        'location_name' => 'Main Office',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice'));
    $clinic = Clinic::where('clinic_name', 'Test Dental Clinic')->sole();
    $copies = VerificationTemplateVersion::where('clinic_id', $clinic->id)->get()->keyBy('form_type');
    expect($copies->keys()->sort()->values()->all())->toBe($availableForms);
    foreach ($availableForms as $form) {
        expect($copies[$form]->source_version_id)->toBe($master->id)
            ->and($copies[$form]->{'active_'.$form})->toBeTrue();
    }
})->with([
    'no released forms' => [[]],
    'only Full released' => [['full_form']],
    'both forms released' => [['full_form', 'short_form']],
]);

test('public registration is closed by default', function () {
    config()->set('prodental.public_registration', false);

    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});
