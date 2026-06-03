<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class InsuranceCarrierNetworkProfile extends Model
{
    public const OUT_OF_NETWORK_OPTIONS = [
        'yes' => 'Yes',
        'no' => 'No',
        'limited' => 'Limited',
        'unknown' => 'Unknown',
    ];

    public const REIMBURSEMENT_DESTINATION_OPTIONS = [
        'provider' => 'Provider',
        'member' => 'Patient / Member',
        'depends' => 'Depends on plan / assignment',
        'unknown' => 'Unknown',
    ];

    protected $fillable = [
        'insurance_carrier_id',
        'participating_provider_summary',
        'non_participating_provider_summary',
        'participating_reimbursement_basis',
        'non_participating_reimbursement_basis',
        'out_of_network_coverage',
        'assignment_of_benefits',
        'reimbursement_destination',
        'balance_billing_note',
        'specialist_rule_notes',
        'fee_schedule_reference_name',
        'fee_schedule_reference_file_path',
        'fee_schedule_reference_external_url',
        'verification_tips',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function insuranceCarrier(): BelongsTo
    {
        return $this->belongsTo(InsuranceCarrier::class);
    }

    public static function resolveFor(?string $carrierName, ?string $payerId = null): ?self
    {
        $carrierName = trim((string) $carrierName);
        $payerId = trim((string) $payerId);

        if ($carrierName === '' && $payerId === '') {
            return null;
        }

        return static::query()
            ->with('insuranceCarrier')
            ->where('is_active', true)
            ->whereHas('insuranceCarrier', function ($query) use ($carrierName, $payerId): void {
                $query->where('is_active', true);

                if ($payerId !== '' && $carrierName !== '') {
                    $query->where(function ($carrierQuery) use ($carrierName, $payerId): void {
                        $carrierQuery
                            ->where('payer_id', $payerId)
                            ->orWhereRaw('LOWER(insurance_name) = ?', [mb_strtolower($carrierName)]);
                    });

                    return;
                }

                if ($payerId !== '') {
                    $query->where('payer_id', $payerId);

                    return;
                }

                $query->whereRaw('LOWER(insurance_name) = ?', [mb_strtolower($carrierName)]);
            })
            ->latest('id')
            ->first();
    }

    public function summaryRows(): array
    {
        return array_values(array_filter([
            [
                'label' => 'Participating Provider',
                'value' => $this->participating_provider_summary,
            ],
            [
                'label' => 'Non-Participating Provider',
                'value' => $this->non_participating_provider_summary,
            ],
            [
                'label' => 'Participating Reimbursement Basis',
                'value' => $this->participating_reimbursement_basis,
            ],
            [
                'label' => 'Non-Participating Reimbursement Basis',
                'value' => $this->non_participating_reimbursement_basis,
            ],
            [
                'label' => 'Out-of-Network Coverage',
                'value' => static::OUT_OF_NETWORK_OPTIONS[$this->out_of_network_coverage] ?? $this->out_of_network_coverage,
            ],
            [
                'label' => 'Assignment of Benefits',
                'value' => $this->assignment_of_benefits,
            ],
            [
                'label' => 'Payment Destination',
                'value' => static::REIMBURSEMENT_DESTINATION_OPTIONS[$this->reimbursement_destination] ?? $this->reimbursement_destination,
            ],
            [
                'label' => 'Balance Billing',
                'value' => $this->balance_billing_note,
            ],
            [
                'label' => 'Specialist / General Dentist Rules',
                'value' => $this->specialist_rule_notes,
            ],
            [
                'label' => 'Fee Schedule Reference',
                'value' => $this->feeScheduleReferenceName(),
            ],
            [
                'label' => 'Verification Tips',
                'value' => $this->verification_tips,
            ],
        ], fn (array $row): bool => filled($row['value'])));
    }

    public function feeScheduleReferenceName(): ?string
    {
        return filled($this->fee_schedule_reference_name)
            ? $this->fee_schedule_reference_name
            : null;
    }

    public function feeScheduleReferenceUrl(): ?string
    {
        if (filled($this->fee_schedule_reference_external_url)) {
            return $this->fee_schedule_reference_external_url;
        }

        if (filled($this->fee_schedule_reference_file_path)) {
            return Storage::disk('public')->url($this->fee_schedule_reference_file_path);
        }

        return null;
    }

    public function hasFeeScheduleReference(): bool
    {
        return filled($this->feeScheduleReferenceName()) || filled($this->feeScheduleReferenceUrl());
    }
}
