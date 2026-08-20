<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ada_procedure_codes')) {
            return;
        }

        Schema::table('ada_procedure_codes', function (Blueprint $table): void {
            if (! Schema::hasColumn('ada_procedure_codes', 'lifecycle_status')) {
                $table->string('lifecycle_status', 40)->default('active')->after('is_active')->index();
            }

            if (! Schema::hasColumn('ada_procedure_codes', 'effective_date')) {
                $table->date('effective_date')->nullable()->after('source_page');
            }

            if (! Schema::hasColumn('ada_procedure_codes', 'retired_at')) {
                $table->timestamp('retired_at')->nullable()->after('effective_date');
            }

            if (! Schema::hasColumn('ada_procedure_codes', 'retirement_reason')) {
                $table->text('retirement_reason')->nullable()->after('retired_at');
            }

            if (! Schema::hasColumn('ada_procedure_codes', 'governance_notes')) {
                $table->text('governance_notes')->nullable()->after('retirement_reason');
            }

            if (! Schema::hasColumn('ada_procedure_codes', 'last_reviewed_at')) {
                $table->timestamp('last_reviewed_at')->nullable()->after('governance_notes');
            }

            if (! Schema::hasColumn('ada_procedure_codes', 'last_reviewed_by')) {
                $table->foreignId('last_reviewed_by')->nullable()->after('last_reviewed_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ada_procedure_codes')) {
            return;
        }

        Schema::table('ada_procedure_codes', function (Blueprint $table): void {
            foreach ([
                'last_reviewed_by',
                'last_reviewed_at',
                'governance_notes',
                'retirement_reason',
                'retired_at',
                'effective_date',
                'lifecycle_status',
            ] as $column) {
                if (Schema::hasColumn('ada_procedure_codes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
