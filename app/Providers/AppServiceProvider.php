<?php

namespace App\Providers;

use App\Models\AdaProcedureCode;
use App\Models\AuditLog;
use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemAttachment;
use App\Models\Clinic;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\PatientDocument;
use App\Models\Payment;
use App\Models\PortalCredential;
use App\Models\SaasSetting;
use App\Models\User;
use App\Models\UserMailbox;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationFormSubmission;
use App\Models\VerificationInboxAttachment;
use App\Models\VerificationInboxMessage;
use App\Models\VerificationNotification;
use App\Models\VerificationProfile;
use App\Models\VerificationTemplateVersion;
use App\Policies\AdaProcedureCodePolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\BillingWorkItemAttachmentPolicy;
use App\Policies\BillingWorkItemPolicy;
use App\Policies\ClinicPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\PatientDocumentPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PortalCredentialPolicy;
use App\Policies\SaasSettingPolicy;
use App\Policies\UserMailboxPolicy;
use App\Policies\UserPolicy;
use App\Policies\VerificationFormQuestionPolicy;
use App\Policies\VerificationFormSubmissionPolicy;
use App\Policies\VerificationInboxAttachmentPolicy;
use App\Policies\VerificationInboxMessagePolicy;
use App\Policies\VerificationNotificationPolicy;
use App\Policies\VerificationProfilePolicy;
use App\Policies\VerificationTemplateVersionPolicy;
use App\Services\Notifications\ProductNotificationService;
use App\Support\ClinicAdministrationSupportAudit;
use App\Support\ClinicTemplateSettingsSupportAudit;
use App\Support\PortalCredentialSupportAudit;
use App\Support\ProviderSupportAudit;
use App\Support\SaasEntitlementAudit;
use App\Support\SaasMailSettings;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(AdaProcedureCode::class, AdaProcedureCodePolicy::class);
        Gate::policy(BillingWorkItem::class, BillingWorkItemPolicy::class);
        Gate::policy(BillingWorkItemAttachment::class, BillingWorkItemAttachmentPolicy::class);
        Gate::policy(Clinic::class, ClinicPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(PatientDocument::class, PatientDocumentPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(PortalCredential::class, PortalCredentialPolicy::class);
        Gate::policy(SaasSetting::class, SaasSettingPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(UserMailbox::class, UserMailboxPolicy::class);
        Gate::policy(VerificationFormQuestion::class, VerificationFormQuestionPolicy::class);
        Gate::policy(VerificationFormSubmission::class, VerificationFormSubmissionPolicy::class);
        Gate::policy(VerificationInboxAttachment::class, VerificationInboxAttachmentPolicy::class);
        Gate::policy(VerificationInboxMessage::class, VerificationInboxMessagePolicy::class);
        Gate::policy(VerificationNotification::class, VerificationNotificationPolicy::class);
        Gate::policy(VerificationProfile::class, VerificationProfilePolicy::class);
        Gate::policy(VerificationTemplateVersion::class, VerificationTemplateVersionPolicy::class);

        try {
            // Use Notification Centre SMTP settings as the primary runtime mail
            // source whenever they are fully configured, while keeping .env as a
            // safe fallback during install or partial setup.
            SaasMailSettings::applyRuntimeDefaultsFromSettings();
        } catch (Throwable) {
            // Fall back silently to the base Laravel mail configuration.
        }

        SaasEntitlementAudit::register();
        ProviderSupportAudit::register();
        PortalCredentialSupportAudit::register();
        ClinicTemplateSettingsSupportAudit::register();
        ClinicAdministrationSupportAudit::register();
        $this->registerSecurityNotificationListeners();
        $this->guardDestructiveLocalDatabaseCommands();
    }

    protected function registerSecurityNotificationListeners(): void
    {
        Event::listen(Lockout::class, function (Lockout $event): void {
            $email = $event->request?->string('email')->toString();
            $user = filled($email) ? User::query()->where('email', $email)->first() : null;

            if (! $user) {
                return;
            }

            app(ProductNotificationService::class)->accountSecurity(
                $user,
                'account_locked',
                'Account temporarily locked',
                'Multiple unsuccessful sign-in attempts temporarily locked your account.',
                'security.lockout.'.$user->getKey().'.'.now()->format('YmdHi'),
            );
        });

        Event::listen(PasswordReset::class, function (PasswordReset $event): void {
            if ($event->user instanceof User) {
                app(ProductNotificationService::class)->accountSecurity(
                    $event->user,
                    'password_reset',
                    'Password changed',
                    'Your ProDental password was successfully changed.',
                    'security.password-reset.'.$event->user->getKey().'.'.now()->timestamp,
                );
            }
        });

        Event::listen(Verified::class, function (Verified $event): void {
            if ($event->user instanceof User) {
                app(ProductNotificationService::class)->accountSecurity(
                    $event->user,
                    'account_verified',
                    'Account verified',
                    'Your ProDental account email was successfully verified.',
                    'security.verified.'.$event->user->getKey(),
                );
            }
        });
    }

    protected function guardDestructiveLocalDatabaseCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            $blockedCommands = [
                'db:wipe',
                'migrate:fresh',
                'migrate:refresh',
                'migrate:reset',
            ];

            if (! in_array($event->command, $blockedCommands, true)) {
                return;
            }

            $connection = config('database.default');
            $database = config("database.connections.{$connection}.database");
            $usesIsolatedTestDatabase = $connection === 'sqlite' && $database === ':memory:';
            $explicitlyAllowed = filter_var(env('PRODENTAL_ALLOW_DESTRUCTIVE_DB_COMMANDS', false), FILTER_VALIDATE_BOOL);

            if ($usesIsolatedTestDatabase || $explicitlyAllowed) {
                return;
            }

            throw new RuntimeException(sprintf(
                'Blocked "%s" for data safety. This command can wipe local project data. '.
                'Set PRODENTAL_ALLOW_DESTRUCTIVE_DB_COMMANDS=true only when you intentionally want to reset this database.',
                $event->command,
            ));
        });
    }
}
