<?php

use App\Filament\Saas\Resources\Verifications\Pages\EditVerificationRequest;

it('counts explicit negative and zero answers without counting empty values', function () {
    $page = new EditVerificationRequest;
    foreach ([0, '0', false, 'No', ['No']] as $value) {
        expect($page->templateAnswerIsPresent($value))->toBeTrue();
    }
    foreach ([null, '', '  ', [], ['', null]] as $value) {
        expect($page->templateAnswerIsPresent($value))->toBeFalse();
    }
});

it('requires both configured answer parts and counts each question once', function () {
    $page = new EditVerificationRequest;
    $page->data = ['primary' => 'No', 'secondary' => null, 'amount' => 0];
    $paired = ['id' => 1, 'field' => 'primary', 'secondary_field' => 'secondary'];
    $single = ['id' => 2, 'field' => 'amount'];
    $group = ['questions' => [$paired, $single, $single], 'benefits' => []];
    expect($page->templateGroupProgress($group))->toBe(['answered' => 1, 'total' => 2]);
    $page->data['secondary'] = 0;
    expect($page->templateGroupProgress($group))->toBe(['answered' => 2, 'total' => 2]);
    expect($page->templateGroupProgress(['questions' => [], 'benefits' => []]))
        ->toBe(['answered' => 0, 'total' => 0]);
});

it('honors configured conditional procedure answer requirements', function () {
    $page = new EditVerificationRequest;
    $row = ['coverage_percent' => 0, 'pre_auth_required' => 'Yes', 'pre_auth_details' => '',
        'response_configuration' => ['required_any' => ['coverage_percent'],
            'required_when' => ['when_field' => 'pre_auth_required', 'when_value' => 'Yes', 'field' => 'pre_auth_details']]];
    expect($page->codeCoverageRowIsComplete($row))->toBeFalse();
    $row['pre_auth_details'] = 'Confirmed';
    expect($page->codeCoverageRowIsComplete($row))->toBeTrue();
    $group = ['questions' => [], 'benefits' => [['index' => 0, 'row' => $row], ['index' => 0, 'row' => $row]]];
    expect($page->templateGroupProgress($group))->toBe(['answered' => 1, 'total' => 1]);
});
