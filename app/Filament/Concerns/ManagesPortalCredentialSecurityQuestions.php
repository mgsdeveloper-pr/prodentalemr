<?php

namespace App\Filament\Concerns;

use App\Models\AuditLog;
use App\Models\PortalCredential;
use App\Models\PortalCredentialSecurityQuestion;
use Filament\Notifications\Notification;

trait ManagesPortalCredentialSecurityQuestions
{
    public bool $securityQuestionsModalOpen = false;

    public ?int $securityQuestionsCredentialId = null;

    public ?string $securityQuestionsCredentialName = null;

    public array $securityQuestionRows = [];

    public array $revealedPortalCredentialValues = [];

    public function openSecurityQuestions(int $credentialId): void
    {
        $credential = $this->resolveAccessibleCredential($credentialId);

        $this->securityQuestionsCredentialId = $credential->getKey();
        $this->securityQuestionsCredentialName = $credential->portal_name;
        $this->securityQuestionRows = $credential->securityQuestions
            ->map(fn (PortalCredentialSecurityQuestion $question): array => [
                'id' => $question->getKey(),
                'question' => $question->question,
                'masked_answer' => PortalCredential::maskSecret($question->answer),
                'is_required' => (bool) $question->is_required,
            ])
            ->values()
            ->all();
        $this->securityQuestionsModalOpen = true;
    }

    public function closeSecurityQuestions(): void
    {
        $this->securityQuestionsModalOpen = false;
        $this->securityQuestionsCredentialId = null;
        $this->securityQuestionsCredentialName = null;
        $this->securityQuestionRows = [];

        foreach (array_keys($this->revealedPortalCredentialValues) as $targetId) {
            if (str_starts_with((string) $targetId, 'portal-security-answer-')) {
                unset($this->revealedPortalCredentialValues[$targetId]);
            }
        }
    }

    public function revealSecurityQuestionAnswer(int $credentialId, int $questionId): void
    {
        [$credential, $question] = $this->resolveSecurityQuestion($credentialId, $questionId);
        $this->recordSecurityQuestionAccess($credential, $question, 'revealed');

        $this->revealPortalCredentialValue(
            "portal-security-answer-{$question->getKey()}",
            (string) $question->answer,
        );
    }

    public function copySecurityQuestionAnswer(int $credentialId, int $questionId): string
    {
        [$credential, $question] = $this->resolveSecurityQuestion($credentialId, $questionId);
        $this->recordSecurityQuestionAccess($credential, $question, 'copied');

        Notification::make()
            ->success()
            ->title('Security answer copied')
            ->send();

        return (string) $question->answer;
    }

    protected function resolveSecurityQuestion(int $credentialId, int $questionId): array
    {
        $credential = $this->resolveAccessibleCredential($credentialId);
        $question = $credential->securityQuestions()->findOrFail($questionId);

        return [$credential, $question];
    }

    protected function recordSecurityQuestionAccess(
        PortalCredential $credential,
        PortalCredentialSecurityQuestion $question,
        string $action,
    ): void {
        AuditLog::query()->forceCreate([
            'user_id' => auth()->id(),
            'organization_id' => $credential->organization_id,
            'clinic_id' => $credential->clinic_id,
            'module' => 'portal_credentials',
            'action' => "security_answer_{$action}",
            'old_values' => null,
            'new_values' => json_encode([
                'portal_credential_id' => $credential->getKey(),
                'security_question_id' => $question->getKey(),
                'portal_name' => $credential->portal_name,
                'access' => $action,
            ], JSON_THROW_ON_ERROR),
            'ip_address' => request()->ip(),
            'device_info' => request()->userAgent(),
        ]);
    }

    public function portalCredentialDisplayValue(string $targetId, string $maskedValue): string
    {
        $revealed = $this->revealedPortalCredentialValues[$targetId] ?? null;

        if (! is_array($revealed) || (int) ($revealed['expires_at'] ?? 0) <= now()->timestamp) {
            return $maskedValue;
        }

        return (string) ($revealed['value'] ?? $maskedValue);
    }

    public function clearExpiredPortalCredentialValues(): void
    {
        $now = now()->timestamp;

        $this->revealedPortalCredentialValues = array_filter(
            $this->revealedPortalCredentialValues,
            fn (mixed $item): bool => is_array($item) && (int) ($item['expires_at'] ?? 0) > $now,
        );
    }

    protected function revealPortalCredentialValue(string $targetId, string $value): void
    {
        $this->revealedPortalCredentialValues[$targetId] = [
            'value' => $value,
            'expires_at' => now()->addSeconds(30)->timestamp,
        ];
    }
}
