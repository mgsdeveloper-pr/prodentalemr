<?php

use App\Support\PanelPermissionMatrix;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_template_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('template_key')->default('template_2');
            $table->string('section_key');
            $table->string('parent_section_key')->nullable();
            $table->string('label');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_builtin')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['clinic_id', 'template_key', 'section_key'], 'verification_template_sections_unique');
            $table->index(['clinic_id', 'template_key', 'parent_section_key'], 'verification_template_sections_parent_index');
        });

        foreach (['verification', 'clinic', 'saas'] as $panel) {
            foreach (array_keys(PanelPermissionMatrix::ACTIONS) as $action) {
                Permission::findOrCreate(PanelPermissionMatrix::permissionName($panel, 'template_management', $action), 'web');
            }
        }

        $this->grantToRole('verification_admin', PanelPermissionMatrix::permissionNamesForModule('verification', 'template_management'));
        $this->grantToRole('verification_manager', [
            PanelPermissionMatrix::permissionName('verification', 'template_management', 'view'),
            PanelPermissionMatrix::permissionName('verification', 'template_management', 'add'),
            PanelPermissionMatrix::permissionName('verification', 'template_management', 'update'),
        ]);
        $this->grantToRole('clinic_admin', PanelPermissionMatrix::permissionNamesForModule('clinic', 'template_management'));
        $this->grantToRole('clinic_manager', [
            PanelPermissionMatrix::permissionName('clinic', 'template_management', 'view'),
            PanelPermissionMatrix::permissionName('clinic', 'template_management', 'add'),
            PanelPermissionMatrix::permissionName('clinic', 'template_management', 'update'),
        ]);
        $this->grantToRole('saas_admin', PanelPermissionMatrix::permissionNamesForModule('saas', 'template_management'));
        $this->grantToRole('saas_manager', [
            PanelPermissionMatrix::permissionName('saas', 'template_management', 'view'),
            PanelPermissionMatrix::permissionName('saas', 'template_management', 'add'),
            PanelPermissionMatrix::permissionName('saas', 'template_management', 'update'),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_template_sections');
    }

    protected function grantToRole(string $roleName, array $permissionNames): void
    {
        $role = Role::findOrCreate($roleName, 'web');
        $permissions = Permission::query()
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->get();

        $role->givePermissionTo($permissions);
    }
};
