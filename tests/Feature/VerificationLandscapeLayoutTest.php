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
    $sections->put(3, [
        'title' => 'Deductible & Coverage Category',
        'rows' => [
            ['kind' => 'coverage_matrix', 'label' => 'Diagnostic', 'deductible' => 'No', 'percent' => '100%'],
            ['kind' => 'coverage_matrix', 'label' => 'Basic', 'deductible' => 'Yes', 'percent' => '80%'],
            ['kind' => 'coverage_matrix', 'label' => 'Endodontics', 'deductible' => 'Yes', 'percent' => '80%'],
            ['kind' => 'coverage_matrix', 'label' => 'Periodontics', 'deductible' => 'Yes', 'percent' => '80%'],
            ['kind' => 'coverage_matrix', 'label' => 'Oral Surgery', 'deductible' => 'Yes', 'percent' => '0%'],
            ['kind' => 'coverage_matrix', 'label' => 'Major', 'deductible' => 'Yes', 'percent' => '50%'],
            ['kind' => 'coverage_matrix', 'label' => 'Orthodontics', 'deductible' => 'Yes', 'percent' => null],
        ],
    ]);
    [$left, $right] = VerificationLandscapeLayout::columns($sections);
    expect($left->concat($right)->values()->all())->toBe($sections->all())
        ->and($left->count())->toBeGreaterThan(5);

    $html = view('pdf.verifications.custom-landscape', [
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
    ])->render();
    expect($html)->toContain('Deductible Applies</th><th style="width:25%">Coverage %</th>')
        ->toContain('<td class="coverage-answer">No</td><td class="coverage-answer">100%</td>')
        ->toContain('<td class="coverage-answer">Yes</td><td class="coverage-answer">0%</td>')
        ->toContain('<td class="coverage-answer">Yes</td><td class="coverage-answer">-</td>')
        ->toContain('<td class="value">100% | Frequency: twice per year</td>')
        ->not->toContain('No | 100%');
    $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
    $pdf->render();
    expect($pdf->getDomPDF()->getCanvas()->get_page_count())->toBe(1);
    if (getenv('PDF_LAYOUT_QA')) {
        file_put_contents(storage_path('app/qa/coverage-columns.pdf'), $pdf->output());
    }
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
