<?php

use App\Models\Clinic;
use App\Models\PortalCredential;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_credentials', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('clinic_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
        });

        $clinicCount = Clinic::query()->count();
        $singleClinic = $clinicCount === 1
            ? Clinic::query()->select(['id', 'organization_id'])->first()
            : null;

        PortalCredential::query()
            ->with('overrides:portal_credential_id,organization_id,clinic_id')
            ->get()
            ->each(function (PortalCredential $credential) use ($singleClinic): void {
                $distinctOverrideClinics = $credential->overrides
                    ->unique('clinic_id')
                    ->values();

                if ($distinctOverrideClinics->count() === 1) {
                    $override = $distinctOverrideClinics->first();

                    $credential->forceFill([
                        'organization_id' => $override->organization_id,
                        'clinic_id' => $override->clinic_id,
                    ])->saveQuietly();

                    return;
                }

                if ($singleClinic && blank($credential->clinic_id)) {
                    $credential->forceFill([
                        'organization_id' => $singleClinic->organization_id,
                        'clinic_id' => $singleClinic->id,
                    ])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        Schema::table('portal_credentials', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('clinic_id');
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
