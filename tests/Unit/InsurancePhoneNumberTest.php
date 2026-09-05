<?php

use App\Support\InsurancePhoneNumber;

it('normalizes clear North American insurance phone numbers to E.164', function (mixed $input, string $expected): void {
    expect(InsurancePhoneNumber::normalize($input))->toBe($expected);
})->with([
    ['800-555-0199', '+18005550199'],
    ['(800) 555-0199', '+18005550199'],
    ['1 800 555 0199', '+18005550199'],
    ['+1 (800) 555-0199', '+18005550199'],
]);

it('preserves valid international numbers and ambiguous values without losing information', function (): void {
    expect(InsurancePhoneNumber::normalize('+44 20 7946 0958'))->toBe('+442079460958')
        ->and(InsurancePhoneNumber::normalize('555-0199'))->toBe('555-0199')
        ->and(InsurancePhoneNumber::normalize('800-555-0199 ext. 245'))->toBe('800-555-0199 ext. 245')
        ->and(InsurancePhoneNumber::normalize(''))->toBeNull();
});
