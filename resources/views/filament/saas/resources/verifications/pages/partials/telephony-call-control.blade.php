@php
    $edgeTrigger = (bool) ($edgeTrigger ?? false);
    $callingAvailable = (bool) ($callingWorkspace['available'] ?? false);
    $rootClass = $edgeTrigger ? 'vt3-call-tool' : '';
    $triggerClass = $edgeTrigger
        ? 'vt3-call-tool__trigger'.($callingAvailable ? '' : ' is-unavailable')
        : '';
    $triggerStyle = $edgeTrigger
        ? ''
        : 'display:inline-flex;align-items:center;gap:7px;height:40px;padding:0 14px;border:1px solid '.($callingAvailable ? '#0f766e' : '#cbd5e1').';border-radius:10px;background:'.($callingAvailable ? '#0f766e' : '#f8fafc').';color:'.($callingAvailable ? '#ffffff' : '#475569').';font-size:12px;font-weight:850;cursor:pointer;';
    $panelRight = $edgeTrigger ? '50px' : '0';
    $panelTop = $edgeTrigger ? '0' : '48px';
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
    wire:ignore
    class="{{ $rootClass }}"
    x-data="verificationTelephonyControl(@js($telephonyConfig))"
    x-on:keydown.escape.window="open = false"
    x-on:verification-open-telephony.window="config.destination = $event.detail.destination || config.destination; open = true"
    style="{{ $edgeTrigger ? '' : 'position:relative;' }}"
>
    <button
        type="button"
        x-on:click="open = ! open"
        aria-label="Call insurance"
        title="Call insurance"
        class="{{ $triggerClass }}"
        x-bind:class="{ 'is-active': active, 'has-error': error }"
        style="{{ $triggerStyle }}"
    >
        <svg aria-hidden="true" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.8a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.33 1.84.56 2.8.69A2 2 0 0 1 22 16.92z"/></svg>
        @if ($edgeTrigger)
            <span style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;">{{ ($callingWorkspace['available'] ?? false) ? 'Call Insurance' : 'Calling unavailable' }}</span>
        @else
            {{ ($callingWorkspace['available'] ?? false) ? 'Call Insurance' : 'Calling unavailable' }}
        @endif
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition
        x-on:click.outside="if (! active && ! loading && ! ending) open = false"
        style="position:absolute;right:{{ $panelRight }};top:{{ $panelTop }};z-index:80;width:min(360px,calc(100vw - 32px));padding:16px;border:1px solid #cbd5e1;border-radius:8px;background:#ffffff;box-shadow:0 18px 42px rgba(15,23,42,.16);white-space:normal;"
    >
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
            <div>
                <div style="font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;">Insurance call</div>
                <div style="margin-top:3px;font-size:15px;font-weight:850;color:#0f172a;" x-text="config.insuranceName"></div>
                <div style="margin-top:2px;font-size:13px;color:#475569;" x-text="config.destination"></div>
            </div>
            <button type="button" x-show="! active && ! loading && ! ending" x-on:click="open = false" aria-label="Close call panel" title="Close" style="width:30px;height:30px;border:1px solid #dbe4ee;border-radius:6px;background:#ffffff;color:#475569;font-size:18px;cursor:pointer;">&times;</button>
        </div>

        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:12px;">
            <span x-show="config.recordingEnabled" style="padding:4px 7px;border-radius:999px;background:#f0fdfa;color:#0f766e;font-size:10px;font-weight:800;">Recording enabled</span>
            <span x-show="config.aiSummaryEnabled" style="padding:4px 7px;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-size:10px;font-weight:800;">AI summary enabled</span>
        </div>

        <div x-show="error" x-text="error" style="margin-top:12px;padding:9px 10px;border:1px solid #fecaca;border-radius:6px;background:#fef2f2;color:#b91c1c;font-size:12px;"></div>

        <div x-show="trackingWarning" x-text="trackingWarning" style="margin-top:12px;padding:9px 10px;border:1px solid #fde68a;border-radius:6px;background:#fffbeb;color:#92400e;font-size:11px;line-height:1.45;"></div>

        <div x-show="! config.available" x-text="config.unavailableReason" style="margin-top:12px;padding:10px 11px;border:1px solid #fde68a;border-radius:6px;background:#fffbeb;color:#92400e;font-size:12px;line-height:1.45;"></div>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:15px;padding:11px 12px;border:1px solid #e2e8f0;border-radius:7px;background:#f8fafc;">
            <div>
                <div style="font-size:12px;font-weight:800;color:#334155;" x-text="statusLabel"></div>
                <div x-show="statusDetail" x-text="statusDetail" style="margin-top:3px;font-size:10px;line-height:1.4;color:#64748b;"></div>
            </div>
            <span x-show="active" style="font-variant-numeric:tabular-nums;font-size:13px;font-weight:850;color:#047857;" x-text="`LIVE ${formattedDuration}`"></span>
        </div>

        <div x-show="loading || active || ending">
            <div
                x-bind:style="dialPadOpen ? 'max-height:min(520px,calc(100vh - 260px));overflow:auto;visibility:visible;margin-top:10px;border:1px solid #e2e8f0;border-radius:6px;' : 'height:0;overflow:hidden;visibility:hidden;margin-top:0;border:0;'"
            >
                <div id="mightycall-webphone-container"></div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-top:12px;">
            <button type="button" x-show="config.available && ! active && ! loading && ! ending && ! rearming" x-on:click="startCall()" style="grid-column:1/-1;height:40px;border:0;border-radius:7px;background:#0f766e;color:#ffffff;font-size:12px;font-weight:850;cursor:pointer;">
                <span x-text="terminalReported ? 'Call again' : 'Start call'"></span>
            </button>
            <button type="button" x-show="loading && ! active && ! ending" x-on:click="cancelCall()" style="grid-column:1/-1;height:40px;border:1px solid #fda4af;border-radius:7px;background:#fff1f2;color:#be123c;font-size:12px;font-weight:850;cursor:pointer;">Cancel call</button>
            <button type="button" x-show="rearming" disabled style="grid-column:1/-1;height:40px;border:1px solid #cbd5e1;border-radius:7px;background:#f8fafc;color:#64748b;font-size:12px;font-weight:800;">Preparing next call...</button>
            <button type="button" x-show="ending" disabled style="grid-column:1/-1;height:40px;border:1px solid #cbd5e1;border-radius:7px;background:#f8fafc;color:#64748b;font-size:12px;font-weight:800;">Ending call...</button>
            <button type="button" x-show="active && ! ending" x-on:click="toggleMute()" style="height:40px;border:1px solid #cbd5e1;border-radius:7px;background:#ffffff;color:#334155;font-size:12px;font-weight:800;cursor:pointer;" x-text="muted ? 'Unmute' : 'Mute'"></button>
            <button
                type="button"
                x-show="active && ! ending"
                x-on:click="dialPadOpen = ! dialPadOpen; if (dialPadOpen) window.MightyCallWebPhone?.Phone?.Focus?.()"
                x-bind:aria-expanded="dialPadOpen"
                x-bind:aria-pressed="dialPadOpen"
                aria-controls="mightycall-webphone-container"
                title="Open or close keypad"
                x-bind:style="dialPadOpen ? 'height:40px;border:1px solid #0f766e;border-radius:7px;background:#f0fdfa;color:#0f766e;font-size:12px;font-weight:850;cursor:pointer;' : 'height:40px;border:1px solid #cbd5e1;border-radius:7px;background:#ffffff;color:#334155;font-size:12px;font-weight:800;cursor:pointer;'"
            >Keypad</button>
            <button type="button" x-show="active && ! ending" x-on:click="endCall()" style="height:40px;border:0;border-radius:7px;background:#be123c;color:#ffffff;font-size:12px;font-weight:850;cursor:pointer;">End call</button>
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
            ending: false,
            rearming: false,
            muted: false,
            dialPadOpen: false,
            error: '',
            trackingWarning: '',
            statusLabel: 'Ready to call',
            callId: null,
            startedAt: null,
            timer: null,
            phoneMonitor: null,
            duration: 0,
            eventsBound: false,
            phoneInitialized: false,
            wasConnected: false,
            terminalReported: false,
            requestedEndStatus: null,
            requestedEndLabel: '',
            reporting: false,
            pendingReports: [],
            attemptSequence: 0,
            activeAttemptId: 0,
            currentProviderCallId: null,
            completedProviderCallIds: [],
            attemptStartedAt: null,
            callPhase: 'idle',
            readyAfterCallChecks: 0,
            providerCallObserved: false,
            cancelRequested: false,

            get formattedDuration() {
                const minutes = Math.floor(this.duration / 60).toString().padStart(2, '0');
                const seconds = (this.duration % 60).toString().padStart(2, '0');
                return `${minutes}:${seconds}`;
            },

            get statusDetail() {
                if (this.statusLabel === 'Ringing insurer') return 'MightyCall accepted the call and is waiting for an answer.';
                if (this.statusLabel === 'Connected') return 'The insurer answered. The call timer shows connected talk time.';
                if (this.statusLabel === 'Call cancelled') return 'You stopped the call before it connected.';
                if (this.statusLabel === 'Call ended without connection') return 'The call was not answered or was disconnected before connection.';
                if (this.statusLabel === 'Call completed') return 'The connected call ended successfully.';
                return '';
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

            providerCallId(callInfo) {
                return callInfo?.Id || callInfo?.id || null;
            },

            providerEventTime(callInfo) {
                const value = callInfo?.DateTimeUtc || callInfo?.dateTimeUtc || null;
                const timestamp = value ? Date.parse(value) : Number.NaN;

                return Number.isNaN(timestamp) ? null : timestamp;
            },

            eventBelongsToActiveAttempt(callInfo) {
                if (! this.callId || this.terminalReported || ! ['dialing', 'ringing', 'connected'].includes(this.callPhase)) return false;

                const providerCallId = this.providerCallId(callInfo);
                const providerEventTime = this.providerEventTime(callInfo);

                if (providerCallId && this.completedProviderCallIds.includes(providerCallId)) {
                    return false;
                }

                if (providerEventTime && this.attemptStartedAt && providerEventTime < (this.attemptStartedAt - 2000)) {
                    return false;
                }

                if (providerCallId && this.currentProviderCallId && providerCallId !== this.currentProviderCallId) {
                    return false;
                }

                if (providerCallId) this.currentProviderCallId = providerCallId;

                return true;
            },

            bindPhoneEvents() {
                if (this.eventsBound) return;

                const phone = window.MightyCallWebPhone.Phone;
                this.eventsBound = true;

                this.subscribe(phone.OnCallOutgoing, (callInfo) => {
                    if (! this.eventBelongsToActiveAttempt(callInfo)) return;
                    this.callPhase = 'ringing';
                    this.statusLabel = 'Ringing insurer';
                    this.report('ringing', callInfo, 'outgoing');
                });
                this.subscribe(phone.OnCallStarted, (callInfo) => {
                    if (! this.eventBelongsToActiveAttempt(callInfo)) return;
                    if (this.wasConnected) return;

                    this.active = true;
                    this.wasConnected = true;
                    this.providerCallObserved = true;
                    this.loading = false;
                    this.startedAt = Date.now();
                    this.callPhase = 'connected';
                    this.statusLabel = 'Connected';
                    this.report('connected', callInfo, 'started');
                    window.clearInterval(this.timer);
                    this.timer = window.setInterval(() => {
                        this.duration = Math.floor((Date.now() - this.startedAt) / 1000);
                    }, 1000);
                });
                this.subscribe(phone.OnCallCompleted, (callInfo) => {
                    if (! this.eventBelongsToActiveAttempt(callInfo)) return;
                    const completed = this.wasConnected;

                    this.finishCall(
                        completed ? 'completed' : 'failed',
                        completed ? 'Call completed' : 'Call ended without connection',
                        callInfo,
                        'completed',
                    );
                });
                this.subscribe(phone.OnHangUp, () => {
                    if (! this.callId || this.terminalReported || ! this.requestedEndStatus) return;

                    const attemptId = this.activeAttemptId;
                    const status = this.requestedEndStatus || (this.wasConnected ? 'completed' : 'failed');
                    const label = this.requestedEndLabel || (this.wasConnected ? 'Call completed' : 'Call ended without connection');

                    window.setTimeout(() => {
                        if (attemptId === this.activeAttemptId) {
                            this.finishCall(status, label, null, 'hangup', attemptId);
                        }
                    }, 750);
                });
                this.subscribe(phone.OnOffline, () => {
                    if (! this.loading && ! this.active) return;

                    this.error = 'MightyCall went offline. Check the browser microphone and network connection.';
                    this.finishCall('failed', 'Connection lost', null, 'offline', this.activeAttemptId);
                });
                this.subscribe(phone.OnError, (error) => {
                    if (! this.loading && ! this.active) return;

                    const message = typeof error === 'string' ? error : (error?.message || 'MightyCall reported an unknown error.');
                    if (this.active) {
                        this.trackingWarning = `MightyCall reported: ${message}. The call remains active until MightyCall confirms it ended.`;
                        return;
                    }

                    this.error = `MightyCall: ${message}`;
                    this.finishCall('failed', 'Call failed', null, 'error', this.activeAttemptId);
                });
            },

            async preparePhone() {
                await this.loadSdk();

                const phone = window.MightyCallWebPhone.Phone;
                const status = phone.Status?.();

                this.bindPhoneEvents();

                if (! this.phoneInitialized || ['inactive', 'offline'].includes(status)) {
                    window.MightyCallWebPhone.ApplyConfig({ login: config.apiKey, password: config.userKey });
                    phone.Init('mightycall-webphone-container');
                    await this.waitForPhoneReady();
                    this.phoneInitialized = true;
                    return;
                }

                await this.waitForPhoneReady();
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

            async report(status, callInfo = null, providerEvent = null) {
                if (! this.callId) return;
                const providerCallId = callInfo?.Id || callInfo?.id || null;
                this.pendingReports.push({
                    callId: this.callId,
                    status,
                    duration: this.duration,
                    providerCallId,
                    providerEvent,
                });

                if (this.reporting) return;

                this.reporting = true;

                try {
                    while (this.pendingReports.length > 0) {
                        const report = this.pendingReports.shift();

                        try {
                            await this.$wire.updateTelephonyCall(
                                report.callId,
                                report.status,
                                report.duration,
                                report.providerCallId,
                                report.providerEvent,
                            );
                            this.trackingWarning = '';
                        } catch (error) {
                            console.error('Call activity sync failed.', error);
                            this.trackingWarning = 'The call is continuing, but its activity status could not be synchronized. Call Usage may update through the MightyCall webhook.';
                        }
                    }
                } finally {
                    this.reporting = false;
                }
            },

            startPhoneMonitor(attemptId) {
                window.clearInterval(this.phoneMonitor);
                this.readyAfterCallChecks = 0;
                this.phoneMonitor = window.setInterval(() => {
                    if (attemptId !== this.activeAttemptId || this.terminalReported) {
                        window.clearInterval(this.phoneMonitor);
                        return;
                    }

                    const status = window.MightyCallWebPhone?.Phone?.Status?.();
                    const providerReady = ['ready', 'registered'].includes(status);
                    const providerInCall = ['busy', 'call_outgoing', 'call_started'].includes(status);
                    const callWasAccepted = ['ringing', 'connected'].includes(this.callPhase);

                    if (providerInCall) this.providerCallObserved = true;

                    this.readyAfterCallChecks = providerReady && callWasAccepted && this.providerCallObserved
                        ? this.readyAfterCallChecks + 1
                        : 0;

                    if (this.readyAfterCallChecks < 3) return;

                    const connected = this.wasConnected;
                    this.finishCall(
                        connected ? 'completed' : 'failed',
                        connected ? 'Call completed' : 'Call ended without connection',
                        null,
                        'status_ready',
                        attemptId,
                    );
                }, 500);
            },

            async finishCall(status, label, callInfo = null, providerEvent = null, attemptId = this.activeAttemptId) {
                if (attemptId !== this.activeAttemptId || this.terminalReported) return;

                this.terminalReported = true;
                window.clearInterval(this.timer);
                window.clearInterval(this.phoneMonitor);
                const providerCallId = this.providerCallId(callInfo) || this.currentProviderCallId;
                if (providerCallId && ! this.completedProviderCallIds.includes(providerCallId)) {
                    this.completedProviderCallIds = [...this.completedProviderCallIds.slice(-9), providerCallId];
                }
                this.active = false;
                this.loading = false;
                this.ending = false;
                this.muted = false;
                this.dialPadOpen = false;
                this.callPhase = 'finished';
                this.statusLabel = label;
                await this.report(status, callInfo, providerEvent);
                if (attemptId === this.activeAttemptId) this.callId = null;
                if (this.phoneInitialized) {
                    this.rearmPhone(attemptId);
                } else {
                    this.rearming = false;
                }
            },

            async rearmPhone(attemptId) {
                this.rearming = true;

                for (let attempt = 0; attempt < 40; attempt += 1) {
                    if (attemptId !== this.activeAttemptId) return;

                    const status = window.MightyCallWebPhone?.Phone?.Status?.();

                    if (['ready', 'registered'].includes(status)) {
                        this.rearming = false;
                        return;
                    }

                    await new Promise((resolve) => window.setTimeout(resolve, 250));
                }

                if (attemptId === this.activeAttemptId) {
                    this.phoneInitialized = false;
                    this.rearming = false;
                }
            },

            async startCall() {
                if (this.loading || this.active || this.ending || this.rearming) return;

                const attemptId = ++this.attemptSequence;
                this.activeAttemptId = attemptId;
                this.callId = null;
                this.loading = true;
                this.dialPadOpen = false;
                this.error = '';
                this.trackingWarning = '';
                this.statusLabel = 'Connecting to MightyCall';
                this.wasConnected = false;
                this.duration = 0;
                this.terminalReported = false;
                this.requestedEndStatus = null;
                this.requestedEndLabel = '';
                this.currentProviderCallId = null;
                this.attemptStartedAt = Date.now();
                this.callPhase = 'preparing';
                this.providerCallObserved = false;
                this.cancelRequested = false;

                try {
                    const call = await this.$wire.startTelephonyCall(config.destination);
                    if (attemptId !== this.activeAttemptId) return;
                    this.callId = call.public_id;
                    this.config.destination = call.destination;
                    if (this.cancelRequested) {
                        await this.finishCall('failed', 'Call cancelled', null, 'cancelled', attemptId);
                        return;
                    }
                    await this.preparePhone();
                    if (attemptId !== this.activeAttemptId) return;
                    if (this.cancelRequested) {
                        window.MightyCallWebPhone?.Phone?.HangUp();
                        await this.finishCall('failed', 'Call cancelled', null, 'cancelled', attemptId);
                        return;
                    }
                    this.callPhase = 'dialing';
                    this.statusLabel = 'Starting call';
                    window.MightyCallWebPhone.Phone.Call(config.destination);
                    window.MightyCallWebPhone.Phone.Focus?.();
                    this.startPhoneMonitor(attemptId);
                } catch (error) {
                    this.error = error?.message || 'The call could not be started.';
                    await this.finishCall('failed', 'Call failed', null, 'error', attemptId);
                }
            },

            cancelCall() {
                if (this.ending || this.terminalReported) return;

                const attemptId = this.activeAttemptId;
                this.ending = true;
                this.cancelRequested = true;
                this.requestedEndStatus = 'failed';
                this.requestedEndLabel = 'Call cancelled';
                this.statusLabel = 'Cancelling call';
                if (this.callId) {
                    window.MightyCallWebPhone?.Phone?.HangUp();
                    window.setTimeout(() => this.finishCall('failed', 'Call cancelled', null, 'cancelled', attemptId), 1000);
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
                if (this.ending || this.terminalReported) return;

                const attemptId = this.activeAttemptId;
                this.ending = true;
                this.requestedEndStatus = 'completed';
                this.requestedEndLabel = 'Call completed';
                this.statusLabel = 'Ending call';
                window.MightyCallWebPhone?.Phone?.HangUp();
                window.setTimeout(() => this.finishCall('completed', 'Call completed', null, 'hangup', attemptId), 1000);
            },
        });
    </script>
@endonce
