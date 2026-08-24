<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            $table->string('logo_path')->nullable()->after('clinic_npi');
            $table->string('email')->nullable()->after('logo_path');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('fax', 30)->nullable()->after('phone');
            $table->string('website')->nullable()->after('fax');
            $table->string('address')->nullable()->after('website');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('zip_code', 20)->nullable()->after('state');
            $table->string('country', 100)->nullable()->after('zip_code');
            $table->json('business_hours')->nullable()->after('country');
            $table->string('primary_contact_name')->nullable()->after('business_hours');
            $table->string('primary_contact_email')->nullable()->after('primary_contact_name');
            $table->string('primary_contact_phone', 30)->nullable()->after('primary_contact_email');
            $table->string('billing_contact_name')->nullable()->after('primary_contact_phone');
            $table->string('billing_contact_email')->nullable()->after('billing_contact_name');
            $table->string('billing_contact_phone', 30)->nullable()->after('billing_contact_email');
            $table->string('verification_contact_name')->nullable()->after('billing_contact_phone');
            $table->string('verification_contact_email')->nullable()->after('verification_contact_name');
            $table->string('verification_contact_phone', 30)->nullable()->after('verification_contact_email');
            $table->string('report_display_name')->nullable()->after('verification_contact_phone');
            $table->text('report_footer')->nullable()->after('report_display_name');
            $table->string('notification_email')->nullable()->after('report_footer');
            $table->json('notification_preferences')->nullable()->after('notification_email');
        });
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            $table->dropColumn([
                'logo_path', 'email', 'phone', 'fax', 'website', 'address', 'city', 'state',
                'zip_code', 'country', 'business_hours', 'primary_contact_name',
                'primary_contact_email', 'primary_contact_phone', 'billing_contact_name',
                'billing_contact_email', 'billing_contact_phone', 'verification_contact_name',
                'verification_contact_email', 'verification_contact_phone', 'report_display_name',
                'report_footer', 'notification_email', 'notification_preferences',
            ]);
        });
    }
};
