<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\TelephonyAccount;
use App\Models\TelephonyCall;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class MightyCallWebhookController extends Controller
{
    public function __invoke(Request $request, TelephonyAccount $account, string $token): Response
    {
        abort_unless(hash_equals((string) $account->webhook_token, $token), 404);
        abort_unless($account->provider === TelephonyAccount::PROVIDER_MIGHTYCALL, 404);

        $payload = $request->all();
        $event = Str::lower((string) $this->first($payload, [
            'EventType', 'eventType', 'event_type', 'Event', 'event', 'Type', 'type',
        ]));
        $providerCallId = $this->first($payload, [
            'CallId', 'callId', 'call_id', 'Id', 'id', 'Call.ID', 'call.id', 'Body.Id', 'body.id',
        ]);
        $toNumber = $this->normalizePhone((string) $this->first($payload, [
            'To', 'to', 'Called.Phone', 'called.phone', 'Call.To', 'call.to', 'Body.To', 'body.to',
        ]));

        $query = TelephonyCall::query()
            ->where('telephony_account_id', $account->getKey());

        if (filled($providerCallId)) {
            $call = (clone $query)->where('provider_call_id', (string) $providerCallId)->latest('id')->first();
        }

        $call ??= (clone $query)
            ->when(filled($toNumber), fn ($builder) => $builder->where('to_number', $toNumber))
            ->whereNotIn('status', ['completed', 'failed'])
            ->where('created_at', '>=', now()->subHours(12))
            ->latest('id')
            ->first();

        if ($call) {
            $status = $this->statusForEvent($event);
            $effectiveStatus = $status && $call->canTransitionTo($status) ? $status : $call->status;
            $duration = $this->durationSeconds($this->first($payload, [
                'CallDuration', 'callDuration', 'Duration', 'duration', 'DurationSeconds', 'durationSeconds', 'Call.Duration', 'call.duration',
            ]), $call->duration_seconds);
            $recordingUrl = $this->first($payload, [
                'RecordingLink', 'recordingLink', 'recording_link', 'CallRecord', 'callRecord', 'RecordingUrl', 'recordingUrl', 'recording_url',
            ]);
            $recordingUrl = TelephonyCall::normalizeMightyCallRecordingUrl($recordingUrl);

            $providerPayload = $call->provider_payload ?? [];
            $webhookEvents = collect($providerPayload['webhook_events'] ?? [])
                ->push([
                    'event' => $event,
                    'status' => $status,
                    'received_at' => now()->toIso8601String(),
                    'payload' => $payload,
                ])
                ->take(-20)
                ->values()
                ->all();

            $updates = [
                'provider_call_id' => filled($providerCallId) ? (string) $providerCallId : $call->provider_call_id,
                'status' => $effectiveStatus,
                'duration_seconds' => max($call->duration_seconds, $duration),
                'provider_payload' => array_merge($providerPayload, ['webhook_events' => $webhookEvents]),
            ];

            if ($effectiveStatus === 'connected' && ! $call->answered_at) {
                $updates['answered_at'] = now();
            }

            if (! $call->isTerminal() && in_array($effectiveStatus, TelephonyCall::TERMINAL_STATUSES, true)) {
                $updates['ended_at'] = now();
            }

            if ($recordingUrl) {
                $updates['recording_url'] = $recordingUrl;
                $updates['recording_duration_seconds'] = max($call->recording_duration_seconds ?? 0, $duration);
            }

            $call->update($updates);
        }

        $account->forceFill(['last_connected_at' => now()])->saveQuietly();

        return response()->noContent();
    }

    private function first(array $payload, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key);

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    private function statusForEvent(string $event): ?string
    {
        return match (true) {
            Str::contains($event, ['completed', 'finished', 'hangup', 'ended']) => 'completed',
            Str::contains($event, ['failed', 'rejected', 'missed', 'cancelled']) => 'failed',
            Str::contains($event, ['connected', 'answered', 'started']) => 'connected',
            Str::contains($event, ['outgoing', 'ringing', 'initiated']) => 'ringing',
            default => null,
        };
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone) ?: '';
    }

    private function durationSeconds(mixed $duration, int $fallback): int
    {
        if (is_numeric($duration)) {
            return max(0, (int) $duration);
        }

        if (is_string($duration) && preg_match('/^(\d+):(\d{2}):(\d{2})$/', trim($duration), $parts)) {
            return ((int) $parts[1] * 3600) + ((int) $parts[2] * 60) + (int) $parts[3];
        }

        return max(0, $fallback);
    }
}
