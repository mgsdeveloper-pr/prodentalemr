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
        Schema::table('locations', function (Blueprint $table): void {
            $table->json('business_hours')->nullable()->after('phone');
            $table->json('schedule_exceptions')->nullable()->after('business_hours');
        });

        Schema::table('clinics', function (Blueprint $table): void {
            $table->foreignId('default_location_id')
                ->nullable()
                ->after('business_hours')
                ->constrained('locations')
                ->nullOnDelete();
        });

        Schema::table('providers', function (Blueprint $table): void {
            $table->string('license_state', 2)->nullable()->after('license_number');
            $table->date('license_expires_at')->nullable()->after('license_state');
            $table->string('taxonomy_code', 20)->nullable()->after('npi_number');
            $table->text('dea_number')->nullable()->after('taxonomy_code');
            $table->string('credentialing_status', 30)->default('not_started')->after('tax_id');
            $table->date('credentialing_effective_at')->nullable()->after('credentialing_status');
            $table->date('credentialing_expires_at')->nullable()->after('credentialing_effective_at');
            $table->json('additional_licenses')->nullable()->after('credentialing_expires_at');
            $table->json('business_hours')->nullable()->after('additional_licenses');
            $table->json('schedule_exceptions')->nullable()->after('business_hours');
            $table->unsignedSmallInteger('scheduling_buffer_minutes')->default(0)->after('schedule_exceptions');
        });

        Schema::table('clinic_operatories', function (Blueprint $table): void {
            $table->json('business_hours')->nullable()->after('notes');
            $table->json('schedule_exceptions')->nullable()->after('business_hours');
        });

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
