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
            'CallId', 'callId', 'call_id', 'Id', 'id', 'Call.ID', 'call.id',
        ]);
        $toNumber = $this->normalizePhone((string) $this->first($payload, [
            'To', 'to', 'Called.Phone', 'called.phone', 'Call.To', 'call.to',
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
            $duration = (int) ($this->first($payload, [
                'Duration', 'duration', 'DurationSeconds', 'durationSeconds', 'Call.Duration', 'call.duration',
            ]) ?: $call->duration_seconds);
            $recordingUrl = $this->first($payload, [
                'CallRecord', 'callRecord', 'RecordingUrl', 'recordingUrl', 'recording_url',
            ]);

            $updates = [
                'provider_call_id' => filled($providerCallId) ? (string) $providerCallId : $call->provider_call_id,
                'status' => $status ?? $call->status,
                'duration_seconds' => max($call->duration_seconds, $duration),
                'provider_payload' => $payload,
            ];

            if ($status === 'connected' && ! $call->answered_at) {
                $updates['answered_at'] = now();
            }

            if ($status === 'completed' || $status === 'failed') {
                $updates['ended_at'] = now();
            }

            if (filled($recordingUrl) && filter_var($recordingUrl, FILTER_VALIDATE_URL)) {
                $updates['recording_url'] = (string) $recordingUrl;
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
}
