<?php

use App\Models\BillingWorkItem;
use App\Support\VerificationLandscapeLayout;
use Barryvdh\DomPDF\Facade\Pdf;

it('balances complete sections without dropping or reordering any answers', function () {
    $sections = collect([8, 10, 7, 7, 5, 5, 11, 9, 13, 7, 4])->map(
        fn (int $count, int $index): array => [
            'title' => 'Section '.$index,
            'rows' => array_fill(0, $count, ['label' => 'Benefit detail', 'value' => '100% | Frequency: twice per year']),
        ]
    );
    [$left, $right] = VerificationLandscapeLayout::columns($sections);
    expect($left->concat($right)->values()->all())->toBe($sections->all())
        ->and($left->count())->toBeGreaterThan(5);

    $pdf = Pdf::loadView('pdf.verifications.custom-landscape', [
        'sections' => $sections,
        'workItem' => new BillingWorkItem(),
        'state' => [
            'vf_patient_full_name' => 'Sample Patient', 'vf_patient_dob' => '1990-01-01',
            'vf_insurance_provider_name' => 'Sample Insurance', 'vf_patient_identifier' => 'MEMBER-001',
            'vf_group_number' => 'GROUP-001',
        ],
        'summary' => [
            'reference_number' => 'QA-001', 'clinic_name' => 'Sample Clinic',
            'clinic_logo' => null, 'report_footer' => 'Report complete',
        ],
    ])->setPaper('a4', 'landscape');
    $pdf->render();
    expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBe(1);
});

it('accounts for multiline answers when balancing and preserves empty and single sections', function () {
    $sections = collect(range(1, 4))->map(fn (int $id): array => [
        'title' => 'Section '.$id,
        'rows' => [['label' => 'Notes', 'value' => $id === 1 ? str_repeat("Long answer\n", 15) : 'Yes']],
    ]);
    [$left, $right] = VerificationLandscapeLayout::columns($sections);
    expect($left)->toHaveCount(1);
    expect($left->concat($right)->values()->all())->toBe($sections->all());
    expect(VerificationLandscapeLayout::columns([])[0])->toBeEmpty();
    expect(VerificationLandscapeLayout::columns($sections->take(1))[0]->all())->toBe($sections->take(1)->all());
});
