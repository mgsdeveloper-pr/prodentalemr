@php
    $telephonyConfig = [
        'provider' => $callingWorkspace['provider'] ?? 'mightycall',
        'apiKey' => $callingWorkspace['api_key'] ?? '',
        'userKey' => $callingWorkspace['user_key'] ?? '',
        'sdkUrl' => $callingWorkspace['sdk_url'] ?? '',
        'destination' => $destinationNumber,
        'insuranceName' => $insuranceName,
        'recordingEnabled' => (bool) ($callingWorkspace['recording_enabled'] ?? false),
        'aiSummaryEnabled' => (bool) ($callingWorkspace['ai_summary_enabled'] ?? false),
        'available' => (bool) ($callingWorkspace['available'] ?? false),
        'unavailableReason' => $callingWorkspace['reason'] ?? '',
    ];
@endphp

<div
    x-data="verificationTelephonyControl(@js($telephonyConfig))"
    x-on:keydown.escape.window="open = false"
    x-on:verification-open-telephony.window="config.destination = $event.detail.destination || config.destination; open = true"
    style="position:relative;"
>
    <button
        type="button"
        x-on:click="open = ! open"
        aria-label="Call insurance"
        title="Call insurance"
        style="display:inline-flex;align-items:center;gap:7px;height:40px;padding:0 14px;border:1px solid {{ ($callingWorkspace['available'] ?? false) ? '#0f766e' : '#cbd5e1' }};border-radius:10px;background:{{ ($callingWorkspace['available'] ?? false) ? '#0f766e' : '#f8fafc' }};color:{{ ($callingWorkspace['available'] ?? false) ? '#ffffff' : '#475569' }};font-size:12px;font-weight:850;cursor:pointer;"
    >
        <svg aria-hidden="true" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.8a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.33 1.84.56 2.8.69A2 2 0 0 1 22 16.92z"/></svg>
        {{ ($callingWorkspace['available'] ?? false) ? 'Call Insurance' : 'Calling unavailable' }}
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition
        x-on:click.outside="if (! active) open = false"
        style="position:absolute;right:0;top:48px;z-index:80;width:min(360px,calc(100vw - 32px));padding:16px;border:1px solid #cbd5e1;border-radius:8px;background:#ffffff;box-shadow:0 18px 42px rgba(15,23,42,.16);white-space:normal;"
    >
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
            <div>
                <div style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;">Insurance call</div>
                <div style="margin-top:3px;font-size:15px;font-weight:850;color:#0f172a;" x-text="config.insuranceName"></div>
                <div style="margin-top:2px;font-size:13px;color:#475569;" x-text="config.destination"></div>
            </div>
            <button type="button" x-on:click="open = false" aria-label="Close call panel" title="Close" style="width:30px;height:30px;border:1px solid #dbe4ee;border-radius:6px;background:#ffffff;color:#475569;font-size:18px;cursor:pointer;">&times;</button>
        </div>

        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:12px;">
            <span x-show="config.recordingEnabled" style="padding:4px 7px;border-radius:999px;background:#f0fdfa;color:#0f766e;font-size:10px;font-weight:800;">Recording enabled</span>
            <span x-show="config.aiSummaryEnabled" style="padding:4px 7px;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-size:10px;font-weight:800;">AI summary enabled</span>
        </div>

        <div x-show="error" x-text="error" style="margin-top:12px;padding:9px 10px;border:1px solid #fecaca;border-radius:6px;background:#fef2f2;color:#b91c1c;font-size:12px;"></div>

        <div x-show="! config.available" x-text="config.unavailableReason" style="margin-top:12px;padding:10px 11px;border:1px solid #fde68a;border-radius:6px;background:#fffbeb;color:#92400e;font-size:12px;line-height:1.45;"></div>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:15px;padding:11px 12px;border:1px solid #e2e8f0;border-radius:7px;background:#f8fafc;">
            <span style="font-size:12px;font-weight:750;color:#475569;" x-text="statusLabel"></span>
            <span x-show="active" style="font-variant-numeric:tabular-nums;font-size:13px;font-weight:850;color:#0f172a;" x-text="formattedDuration"></span>
        </div>

        <div id="mightycall-webphone-container" style="margin-top:10px;"></div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px;">
            <button type="button" x-show="config.available && ! active" x-bind:disabled="loading" x-on:click="startCall()" style="grid-column:1/-1;height:40px;border:0;border-radius:7px;background:#0f766e;color:#ffffff;font-size:12px;font-weight:850;cursor:pointer;">
                <span x-text="loading ? 'Connecting...' : 'Start call'"></span>
            </button>
            <button type="button" x-show="active" x-on:click="toggleMute()" style="height:40px;border:1px solid #cbd5e1;border-radius:7px;background:#ffffff;color:#334155;font-size:12px;font-weight:800;cursor:pointer;" x-text="muted ? 'Unmute' : 'Mute'"></button>
            <button type="button" x-show="active" x-on:click="endCall()" style="height:40px;border:0;border-radius:7px;background:#be123c;color:#ffffff;font-size:12px;font-weight:850;cursor:pointer;">End call</button>
        </div>
    </div>
</div>

@once
    <script>
        window.verificationTelephonyControl = (config) => ({
            config,
            open: false,
            loading: false,
            active: false,
            muted: false,
            error: '',
            statusLabel: 'Ready to call',
            callId: null,
            startedAt: null,
            timer: null,
            duration: 0,
            eventsBound: false,
            wasConnected: false,

            get formattedDuration() {
                const minutes = Math.floor(this.duration / 60).toString().padStart(2, '0');
                const seconds = (this.duration % 60).toString().padStart(2, '0');
                return `${minutes}:${seconds}`;
            },

            async loadSdk() {
                if (window.MightyCallWebPhone) return;
                if (! config.sdkUrl.startsWith('https://')) throw new Error('The WebPhone service URL is invalid.');

                await new Promise((resolve, reject) => {
                    const existing = document.querySelector(`script[data-mightycall-sdk="${config.sdkUrl}"]`);
                    if (existing) {
                        existing.addEventListener('load', resolve, { once: true });
                        existing.addEventListener('error', reject, { once: true });
                        return;
                    }

                    const script = document.createElement('script');
                    script.src = config.sdkUrl;
                    script.async = true;
                    script.dataset.mightycallSdk = config.sdkUrl;
                    script.onload = resolve;
                    script.onerror = () => reject(new Error('MightyCall WebPhone could not be loaded.'));
                    document.head.appendChild(script);
                });
            },

            subscribe(event, callback) {
                if (event && typeof event.subscribe === 'function') event.subscribe(callback);
            },

            bindPhoneEvents() {
                if (this.eventsBound) return;

                const phone = window.MightyCallWebPhone.Phone;
                this.eventsBound = true;

                this.subscribe(phone.OnCallOutgoing, () => {
                    this.statusLabel = 'Ringing';
                    this.report('ringing');
                });
                this.subscribe(phone.OnCallStarted, () => {
                    this.active = true;
                    this.wasConnected = true;
                    this.loading = false;
                    this.startedAt = Date.now();
                    this.statusLabel = 'Connected';
                    this.report('connected');
                    window.clearInterval(this.timer);
                    this.timer = window.setInterval(() => {
                        this.duration = Math.floor((Date.now() - this.startedAt) / 1000);
                    }, 1000);
                });
                this.subscribe(phone.OnHangUp, () => {
                    const completed = this.wasConnected;

                    window.clearInterval(this.timer);
                    this.active = false;
                    this.loading = false;
                    this.statusLabel = completed ? 'Call completed' : 'Call disconnected before connection';
                    this.report(completed ? 'completed' : 'failed');
                });
            },

            async waitForPhoneReady() {
                const phone = window.MightyCallWebPhone.Phone;
                const readyStatuses = ['ready', 'registered'];

                if (readyStatuses.includes(phone.Status?.())) return;

                await new Promise((resolve, reject) => {
                    let settled = false;
                    let interval;
                    let timeout;

                    const finish = (callback) => {
                        if (settled) return;
                        settled = true;
                        window.clearInterval(interval);
                        window.clearTimeout(timeout);
                        phone.OnReady?.unsubscribe?.(onReady);
                        callback();
                    };
                    const onReady = () => finish(resolve);

                    this.subscribe(phone.OnReady, onReady);
                    interval = window.setInterval(() => {
                        if (readyStatuses.includes(phone.Status?.())) finish(resolve);
                    }, 250);
                    timeout = window.setTimeout(
                        () => finish(() => reject(new Error('MightyCall did not become ready. Check microphone and network access.'))),
                        20000,
                    );
                });
            },

            async report(status) {
                if (! this.callId) return;
                await this.$wire.updateTelephonyCall(this.callId, status, this.duration);
            },

            async startCall() {
                this.loading = true;
                this.error = '';
                this.statusLabel = 'Connecting to MightyCall';
                this.wasConnected = false;
                this.duration = 0;

                try {
                    const call = await this.$wire.startTelephonyCall(config.destination);
                    this.callId = call.public_id;
                    await this.loadSdk();

                    window.MightyCallWebPhone.ApplyConfig({ login: config.apiKey, password: config.userKey });
                    this.bindPhoneEvents();
                    window.MightyCallWebPhone.Phone.Init('mightycall-webphone-container');
                    await this.waitForPhoneReady();
                    this.statusLabel = 'Starting call';
                    window.MightyCallWebPhone.Phone.Call(config.destination);
                } catch (error) {
                    this.error = error?.message || 'The call could not be started.';
                    this.statusLabel = 'Call failed';
                    this.loading = false;
                    await this.report('failed');
                }
            },

            toggleMute() {
                if (! window.MightyCallWebPhone) return;
                if (this.muted) {
                    window.MightyCallWebPhone.Phone.UnMute();
                } else {
                    window.MightyCallWebPhone.Phone.Mute();
                }
                this.muted = ! this.muted;
            },

            async endCall() {
                try {
                    window.MightyCallWebPhone?.Phone?.HangUp();
                } finally {
                    window.clearInterval(this.timer);
                    this.active = false;
                    this.loading = false;
                    this.statusLabel = 'Call completed';
                    await this.report('completed');
                }
            },
        });
    </script>
@endonce
