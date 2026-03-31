/**
 * Admin panel notification handler.
 *
 * Responsibilities:
 *  - play GG-style double-ping sound on incoming Echo notifications
 *  - intercept notification link clicks: mark as read, then navigate
 *  - intercept notification X button: delete from DB via Livewire
 *
 * Config is injected by AdminPanelProvider via window.AdminPanelConfig:
 *   { userId: number }
 */
(function () {
    const cfg = window.AdminPanelConfig ?? {};
    const _notifUserId = cfg.userId;
    if (!_notifUserId) return;

    // ─── Sound ───────────────────────────────────────────────────────────────

    function playNotificationSound() {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const run = () => {
                const t = ctx.currentTime;

                function ggPing(freq, startTime, dur) {
                    const osc  = ctx.createOscillator();
                    const env  = ctx.createGain();
                    const dist = ctx.createWaveShaper();

                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(freq, startTime);
                    osc.frequency.exponentialRampToValueAtTime(freq * 0.78, startTime + dur * 0.9);

                    env.gain.setValueAtTime(0.0001, startTime);
                    env.gain.exponentialRampToValueAtTime(0.30, startTime + 0.008);
                    env.gain.exponentialRampToValueAtTime(0.0001, startTime + dur);

                    const curve = new Float32Array(256);
                    for (let i = 0; i < 256; i++) {
                        const x = (i * 2) / 256 - 1;
                        curve[i] = (Math.PI + 80) * x / (Math.PI + 80 * Math.abs(x));
                    }
                    dist.curve = curve;
                    dist.oversample = '2x';

                    osc.connect(dist);
                    dist.connect(env);
                    env.connect(ctx.destination);
                    osc.start(startTime);
                    osc.stop(startTime + dur + 0.02);
                }

                // Classic GG double-boing: higher then slightly lower
                ggPing(1350, t,        0.18);
                ggPing(1050, t + 0.21, 0.22);
            };
            ctx.state === 'suspended' ? ctx.resume().then(run) : run();
        } catch (e) {}
    }

    // ─── Echo subscription ────────────────────────────────────────────────────

    function subscribeEcho() {
        window.Echo
            .private('App.Models.User.' + _notifUserId)
            .listen('.database-notifications.sent', playNotificationSound);
    }

    window.addEventListener('EchoLoaded', subscribeEcho);
    if (window.Echo) subscribeEcho();

    window.addEventListener('notificationSent', playNotificationSound);

    // ─── Helpers ──────────────────────────────────────────────────────────────

    function getDbNotifWire() {
        const el = document.querySelector('.fi-no-database');
        if (!el) return null;
        const wireId = el.getAttribute('wire:id');
        return wireId ? Livewire.find(wireId) : null;
    }

    // ─── View click: mark as read + navigate, NEVER delete ───────────────────

    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[href]');
        if (!link) return;
        if (!link.closest('.fi-no-notification-unread-ctn, .fi-no-notification-read-ctn')) return;

        const href     = link.getAttribute('href') || '';
        const idMatch  = href.match(/[?&]id=([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i);
        const toMatch  = href.match(/[?&]to=([^&]+)/);

        if (!idMatch || !toMatch) return;

        const notifId     = idMatch[1];
        const destination = decodeURIComponent(toMatch[1]);

        e.preventDefault();
        e.stopPropagation();

        const xsrfMatch = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
        const token     = xsrfMatch ? decodeURIComponent(xsrfMatch[1]) : '';

        fetch('/notification-mark-read', {
            method:    'POST',
            keepalive: true,
            headers:   { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': token },
            body:      JSON.stringify({ id: notifId }),
        }).catch(function () {});

        window.dispatchEvent(new CustomEvent('markedNotificationAsRead', { detail: { id: notifId } }));
        window.location.href = destination;
    }, true);

    // ─── X button: DELETE notification from DB ────────────────────────────────

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.fi-no-notification-close-btn');
        if (!btn) return;
        if (!btn.closest('.fi-no-database')) return; // only panel, not toast

        const notifEl = btn.closest('[x-data]');
        if (!notifEl) return;
        const xData = notifEl.getAttribute('x-data') || '';
        const m = xData.match(/"id"\s*:\s*"([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})"/i);
        if (!m) return;

        const wire = getDbNotifWire();
        if (wire) wire.removeNotification(m[1]);
    }, true);
})();
