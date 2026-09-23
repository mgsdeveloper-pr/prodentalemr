<?php

use Database\Seeders\ByDesignDentalsSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Real client provisioning is tested explicitly, not seeded into every isolated test database.
        if (! app()->environment('testing')) {
            app(ByDesignDentalsSeeder::class)->run();
        }
    }

    public function down(): void
    {
        // Preserve client records and any clinical work created after deployment.
    }
};
