<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verification_template_versions', function (Blueprint $table) {
            $table->boolean('active_short_form')->default(false);
            $table->boolean('active_full_form')->default(false);
        });
        foreach (['short_form', 'full_form'] as $form) {
            DB::table('verification_template_versions')->where('is_active', true)
                ->where('status', 'published')->whereIn('form_type', ['both', $form])
                ->update(['active_'.$form => true]);
        }
        Schema::create('verification_template_imports', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('clinic_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('template_version_id')->nullable()->constrained('verification_template_versions')->restrictOnDelete();
            $table->string('payload_hash', 64);
            $table->string('name');
            $table->string('form_type', 20);
            $table->unsignedInteger('question_count');
            $table->timestamps();
        });
        foreach (['saas', 'clinic', 'verification'] as $panel) {
            foreach (['view', 'add', 'update', 'delete'] as $action) {
                $permission = \Spatie\Permission\Models\Permission::findOrCreate($panel.'.template_publishing.'.$action, 'web');
                if (in_array($action, ['view', 'update'], true)) {
                    \Spatie\Permission\Models\Role::where('name', $panel.'_admin')->where('guard_name', 'web')->first()?->givePermissionTo($permission);
                }
            }
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        \Spatie\Permission\Models\Permission::whereIn('name', collect(['saas', 'clinic', 'verification'])->flatMap(
            fn ($panel) => collect(['view', 'add', 'update', 'delete'])->map(fn ($action) => $panel.'.template_publishing.'.$action)
        )->all())->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Schema::dropIfExists('verification_template_imports');
        Schema::table('verification_template_versions', function (Blueprint $table) {
            $table->dropColumn(['active_short_form', 'active_full_form']);
        });
    }
};
