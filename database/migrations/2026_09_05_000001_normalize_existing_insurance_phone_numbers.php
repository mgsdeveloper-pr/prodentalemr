<?php

use App\Support\InsurancePhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->phoneColumns() as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->select(['id', $column])
                ->whereNotNull($column)
                ->orderBy('id')
                ->chunkById(250, function ($rows) use ($table, $column): void {
                    foreach ($rows as $row) {
                        $normalized = InsurancePhoneNumber::normalize($row->{$column});

                        if ($normalized !== $row->{$column}) {
                            DB::table($table)
                                ->where('id', $row->id)
                                ->update([$column => $normalized]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        // Normalized phone formatting is intentionally retained.
    }

    private function phoneColumns(): array
    {
        return [
            'insurance_carriers' => 'payer_phone',
            'clinic_insurance_carrier_overrides' => 'payer_phone',
            'patient_insurance_policies' => 'payer_phone',
            'verification_profiles' => 'insurance_company_phone_number',
        ];
    }
};
