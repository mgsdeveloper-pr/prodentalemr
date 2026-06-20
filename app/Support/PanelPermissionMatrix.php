<?php

namespace App\Support;

use App\Models\User;

class PanelPermissionMatrix
{
    public const ACTIONS = [
        'add' => 'Add',
        'view' => 'View',
        'update' => 'Update',
        'delete' => 'Delete',
    ];

    public static function modules(string $panel): array
    {
        return match ($panel) {
            'verification' => [
                'verification' => 'Verification',
                'portal_credentials' => 'Portal Credentials',
                'insurance_directory' => 'Insurance Directory',
                'reports' => 'Reports',
                'notifications' => 'Notifications',
                'users' => 'Users',
                'roles_permissions' => 'Roles & Permissions',
                'settings' => 'Verification Settings',
            ],
            'saas' => [
                'verification' => 'Verification',
                'organizations' => 'Organizations',
                'clinics' => 'Clinics',
                'locations' => 'Locations',
                'users' => 'Users',
                'managed_services' => 'Managed Services',
                'client_enrollments' => 'Client Enrollments',
                'invoices' => 'Invoices',
                'payments' => 'Payments',
                'subscription_plans' => 'Subscription Plans',
                'subscriptions' => 'Subscriptions',
                'service_items' => 'Service List',
                'insurance_directory' => 'Insurance Directory',
                'portal_credentials' => 'Portal Credentials',
                'billing_settings' => 'Billing Settings',
                'settings' => 'SaaS Settings',
                'roles_permissions' => 'Roles & Permissions',
            ],
            'clinic' => [
                'users' => 'Users',
                'patients' => 'Patients',
                'providers' => 'Providers',
                'appointments' => 'Appointments',
                'encounters' => 'Encounters',
                'treatment_plans' => 'Treatment Plans',
                'dental_chart_entries' => 'Dental Charting',
                'perio_charts' => 'Perio Charts',
                'clinic_services' => 'Clinic Services',
                'patient_documents' => 'Patient Documents',
                'patient_insurance_policies' => 'Insurance Policies',
                'insurance_directory' => 'Insurance Directory',
                'portal_credentials' => 'Portal Credentials',
                'patient_ledger_entries' => 'Patient Ledger',
                'patient_insurance_claims' => 'Insurance Claims',
                'patient_statements' => 'Patient Statements',
                'clinic_operatories' => 'Operatories',
                'patient_consent_forms' => 'Consent Forms',
                'verification_requests' => 'Verification Requests',
                'managed_services' => 'Managed Services',
                'roles_permissions' => 'Roles & Permissions',
            ],
            'dso' => [
                'dashboard' => 'Dashboard',
                'clinics' => 'Clinics',
                'reports' => 'Reports',
                'users' => 'Users',
                'roles_permissions' => 'Roles & Permissions',
                'settings' => 'Settings',
            ],
            default => [],
        };
    }

    public static function roles(string $panel): array
    {
        return match ($panel) {
            'verification' => User::verificationRoleOptions(),
            'saas' => User::saasRoleOptions(),
            'clinic' => User::clinicRoleOptions(),
            'dso' => User::dsoRoleOptions(),
            default => [],
        };
    }

    public static function adminRole(string $panel): ?string
    {
        return match ($panel) {
            'verification' => 'verification_admin',
            'saas' => 'saas_admin',
            'clinic' => 'clinic_admin',
            'dso' => 'dso_admin',
            default => null,
        };
    }

    public static function permissionName(string $panel, string $module, string $action): string
    {
        return "{$panel}.{$module}.{$action}";
    }

    public static function permissionNamesForPanel(string $panel): array
    {
        $permissions = [];

        foreach (array_keys(static::modules($panel)) as $module) {
            foreach (array_keys(static::ACTIONS) as $action) {
                $permissions[] = static::permissionName($panel, $module, $action);
            }
        }

        return $permissions;
    }
}
