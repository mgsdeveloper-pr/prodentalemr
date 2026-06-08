<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portal_credentials', function (Blueprint $table): void {
            $table->text('registration_qa_notes')->nullable()->after('support_contact');
            $table->text('general_notes')->nullable()->after('registration_qa_notes');
        });

        Schema::table('clinic_portal_credential_overrides', function (Blueprint $table): void {
            $table->text('registration_qa_notes')->nullable()->after('support_contact');
            $table->text('general_notes')->nullable()->after('registration_qa_notes');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_portal_credential_overrides', function (Blueprint $table): void {
            $table->dropColumn(['registration_qa_notes', 'general_notes']);
        });

        Schema::table('portal_credentials', function (Blueprint $table): void {
            $table->dropColumn(['registration_qa_notes', 'general_notes']);
        });
    }
};
