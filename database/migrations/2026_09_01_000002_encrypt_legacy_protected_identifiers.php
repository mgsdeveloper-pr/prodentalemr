<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->encryptPlaintextValues('providers', 'tax_id');
        $this->encryptPlaintextValues('providers', 'dea_number');
        $this->encryptPlaintextValues('clinics', 'tax_id');
    }

    public function down(): void
    {
        // Protected identifiers intentionally remain encrypted.
    }

    private function encryptPlaintextValues(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->select(['id', $column])
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($records) use ($table, $column): void {
                foreach ($records as $record) {
                    $value = (string) $record->{$column};

                    try {
                        Crypt::decryptString($value);

                        continue;
                    } catch (Throwable) {
                        DB::table($table)
                            ->where('id', $record->id)
                            ->update([$column => Crypt::encryptString($value)]);
                    }
                }
            });
    }
};
