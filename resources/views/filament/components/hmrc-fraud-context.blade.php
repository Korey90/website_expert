<script data-navigate-once>
    (() => {
        const cookieName = 'hmrc_fph_context';
        const deviceStorageKey = 'website_expert_ni_hmrc_device_id';

        const uuid = () => {
            if (window.crypto?.randomUUID) {
                return window.crypto.randomUUID();
            }

            const bytes = new Uint8Array(16);
            window.crypto.getRandomValues(bytes);
            bytes[6] = (bytes[6] & 0x0f) | 0x40;
            bytes[8] = (bytes[8] & 0x3f) | 0x80;
            const hex = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');

            return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
        };

        const deviceId = () => {
            let value = window.localStorage.getItem(deviceStorageKey);
            if (!/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(value ?? '')) {
                value = uuid();
                window.localStorage.setItem(deviceStorageKey, value);
            }

            return value;
        };

        const timezone = () => {
            const minutes = -new Date().getTimezoneOffset();
            const sign = minutes >= 0 ? '+' : '-';
            const absolute = Math.abs(minutes);

            return `UTC${sign}${String(Math.floor(absolute / 60)).padStart(2, '0')}:${String(absolute % 60).padStart(2, '0')}`;
        };

        const collect = () => {
            const context = {
                browser_js_user_agent: window.navigator.userAgent,
                device_id: deviceId(),
                screens: [{
                    width: window.screen.width,
                    height: window.screen.height,
                    scaling_factor: window.devicePixelRatio || 1,
                    colour_depth: window.screen.colorDepth,
                }],
                timezone: timezone(),
                window_size: {
                    width: window.innerWidth,
                    height: window.innerHeight,
                },
                collected_at: new Date().toISOString(),
            };

            const secure = window.location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = `${cookieName}=${encodeURIComponent(JSON.stringify(context))}; Path=/; Max-Age=31536000; SameSite=Lax${secure}`;
        };

        let resizeTimer;
        window.addEventListener('resize', () => {
            window.clearTimeout(resizeTimer);
            resizeTimer = window.setTimeout(collect, 250);
        });
        document.addEventListener('livewire:navigated', collect);
        collect();
    })();
</script>
