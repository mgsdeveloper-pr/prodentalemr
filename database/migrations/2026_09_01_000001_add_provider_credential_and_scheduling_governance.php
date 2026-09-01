<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $locationBusinessHoursMissing = ! Schema::hasColumn('locations', 'business_hours');
        $locationExceptionsMissing = ! Schema::hasColumn('locations', 'schedule_exceptions');

        if ($locationBusinessHoursMissing || $locationExceptionsMissing) {
            Schema::table('locations', function (Blueprint $table) use ($locationBusinessHoursMissing, $locationExceptionsMissing): void {
                if ($locationBusinessHoursMissing) {
                    $table->json('business_hours')->nullable()->after('phone');
                }

                if ($locationExceptionsMissing) {
                    $table->json('schedule_exceptions')->nullable()->after('business_hours');
                }
            });
        }

        if (! Schema::hasColumn('clinics', 'default_location_id')) {
            Schema::table('clinics', function (Blueprint $table): void {
                $table->foreignId('default_location_id')
                    ->nullable()
                    ->after('business_hours')
                    ->constrained('locations')
                    ->nullOnDelete();
            });
        }

        $providerColumns = [
            'license_state' => fn (Blueprint $table) => $table->string('license_state', 2)->nullable()->after('license_number'),
            'license_expires_at' => fn (Blueprint $table) => $table->date('license_expires_at')->nullable()->after('license_state'),
            'taxonomy_code' => fn (Blueprint $table) => $table->string('taxonomy_code', 20)->nullable()->after('npi_number'),
            'dea_number' => fn (Blueprint $table) => $table->text('dea_number')->nullable()->after('taxonomy_code'),
            'credentialing_status' => fn (Blueprint $table) => $table->string('credentialing_status', 30)->default('not_started')->after('tax_id'),
            'credentialing_effective_at' => fn (Blueprint $table) => $table->date('credentialing_effective_at')->nullable()->after('credentialing_status'),
            'credentialing_expires_at' => fn (Blueprint $table) => $table->date('credentialing_expires_at')->nullable()->after('credentialing_effective_at'),
            'additional_licenses' => fn (Blueprint $table) => $table->json('additional_licenses')->nullable()->after('credentialing_expires_at'),
            'business_hours' => fn (Blueprint $table) => $table->json('business_hours')->nullable()->after('additional_licenses'),
            'schedule_exceptions' => fn (Blueprint $table) => $table->json('schedule_exceptions')->nullable()->after('business_hours'),
            'scheduling_buffer_minutes' => fn (Blueprint $table) => $table->unsignedSmallInteger('scheduling_buffer_minutes')->default(0)->after('schedule_exceptions'),
        ];

        foreach ($providerColumns as $column => $definition) {
            if (! Schema::hasColumn('providers', $column)) {
                Schema::table('providers', $definition);
            }
        }

        $operatoryBusinessHoursMissing = ! Schema::hasColumn('clinic_operatories', 'business_hours');
        $operatoryExceptionsMissing = ! Schema::hasColumn('clinic_operatories', 'schedule_exceptions');

        if ($operatoryBusinessHoursMissing || $operatoryExceptionsMissing) {
            Schema::table('clinic_operatories', function (Blueprint $table) use ($operatoryBusinessHoursMissing, $operatoryExceptionsMissing): void {
                if ($operatoryBusinessHoursMissing) {
                    $table->json('business_hours')->nullable()->after('notes');
                }

                if ($operatoryExceptionsMissing) {
                    $table->json('schedule_exceptions')->nullable()->after('business_hours');
                }
            });
        }

        DB::table('providers')
            ->whereNotNull('tax_id')
            ->where('tax_id', '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($providers): void {
                foreach ($providers as $provider) {
                    $value = (string) $provider->tax_id;

                    try {
                        Crypt::decryptString($value);

                        continue;
                    } catch (Throwable) {
                        DB::table('providers')
                            ->where('id', $provider->id)
                            ->update(['tax_id' => Crypt::encryptString($value)]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('default_location_id');
        });

        Schema::table('locations', function (Blueprint $table): void {
            $table->dropColumn(['business_hours', 'schedule_exceptions']);
        });

        Schema::table('providers', function (Blueprint $table): void {
            $table->dropColumn([
                'license_state',
                'license_expires_at',
                'taxonomy_code',
                'dea_number',
                'credentialing_status',
                'credentialing_effective_at',
                'credentialing_expires_at',
                'additional_licenses',
                'business_hours',
                'schedule_exceptions',
                'scheduling_buffer_minutes',
            ]);
        });

        Schema::table('clinic_operatories', function (Blueprint $table): void {
            $table->dropColumn(['business_hours', 'schedule_exceptions']);
        });
    }
};
