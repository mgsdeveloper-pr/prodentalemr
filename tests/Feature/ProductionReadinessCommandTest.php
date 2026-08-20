<?php

use Illuminate\Support\Facades\Artisan;

it('blocks deployment when required production configuration is missing', function () {
    config()->set('app.debug', true);
    config()->set('app.url', 'http://localhost');
    config()->set('mail.default', 'log');
    config()->set('session.encrypt', false);
    config()->set('session.secure', false);

    expect(Artisan::call('prodental:production-check'))->toBe(1)
        ->and(Artisan::output())->toContain('Do not deploy');
});
