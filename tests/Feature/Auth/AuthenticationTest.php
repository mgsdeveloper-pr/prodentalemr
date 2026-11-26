<?php

use App\Models\Dso;
use App\Models\SaasSetting;
use App\Models\User;
use Database\Seeders\RoleSeeder;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response
        ->assertStatus(200)
        ->assertSee('Welcome back')
        ->assertSee('Dental operations, connected.')
        ->assertSee('images/login/dental-office-default.png');
});

test('saas admin login image replaces the default image', function () {
    SaasSetting::query()->updateOrCreate(
        ['id' => 1],
        [
            'platform_name' => 'ProDental EMR',
            'login_image_path' => 'branding/login/custom-clinic.webp',
        ],
    );

    $this->get('/login')
        ->assertOk()
        ->assertSee('uploads/branding/login/custom-clinic.webp');
});

test('users can authenticate using the login screen', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'status' => true,
    ]);
    $user->assignRole('saas_admin');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/saas');
});

test('DSO sign-in redirects according to access to the requested workspace', function (string $requestedUrl, string $expectedUrl, bool $hasSaasAccess) {
    $this->seed(RoleSeeder::class);
    $dso = Dso::create(['name' => 'QA Network', 'status' => true]);
    $user = User::factory()->create([
        'dso_id' => $dso->id,
        'email_verified_at' => now(),
        'status' => true,
    ]);
    $user->assignRole('dso_admin');
    if ($hasSaasAccess) {
        $user->assignRole('saas_admin');
    }

    $this->get($requestedUrl)->assertRedirect('/login');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect($expectedUrl)->assertSessionMissing('url.intended');

    $this->assertAuthenticatedAs($user);
})->with([
    'SaaS home without access' => ['/saas', '/dso', false],
    'SaaS deep link without access' => ['/saas/client-management?search=demo', '/dso', false],
    'DSO deep link' => ['/dso/clinics', '/dso/clinics', false],
    'SaaS deep link with access' => ['/saas/client-management', '/saas/client-management', true],
]);

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create(['status' => true]);

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('inactive users cannot sign in or update their sign in timestamp', function () {
    $user = User::factory()->create(['status' => false, 'last_login_at' => null]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => true])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
    expect($user->fresh()->last_login_at)->toBeNull();
});

test('successful sign ins record the time but failed attempts do not', function () {
    $this->freezeTime();
    $user = User::factory()->create(['status' => true, 'last_login_at' => null]);

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
    expect($user->fresh()->last_login_at)->toBeNull();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    expect($user->fresh()->last_login_at->toDateTimeString())->toBe(now()->toDateTimeString());
});

test('disabling a signed in user revokes access without changing their profile', function (string $path) {
    $this->seed(RoleSeeder::class);
    $user = User::factory()->create(['status' => true]);
    $user->assignRole('saas_admin');
    $this->actingAs($user);
    $user->update(['status' => false]);

    $this->withSession(['clinic_workspace' => 'verification'])->get($path)
        ->assertRedirect('/login');
    $this->assertGuest();
    expect($user->fresh()->exists)->toBeTrue();
})->with(['/profile', '/saas/user-management', '/clinic/users', '/verification/users']);

test('inactive users cannot submit profile changes or authenticated json requests', function () {
    $user = User::factory()->create(['status' => false, 'name' => 'Unchanged']);
    $this->actingAs($user)->patch('/profile', ['name' => 'Changed', 'email' => $user->email])
        ->assertRedirect('/login');
    expect($user->fresh()->name)->toBe('Unchanged');

    $this->actingAs($user)->getJson('/profile')->assertUnauthorized();
    $this->assertGuest();
});
