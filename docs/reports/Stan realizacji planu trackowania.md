## Stan realizacji planu — ✅ Wszystko zrobione

---

### Co zostało zbudowane razem (podsumowanie całości)

#### Infrastruktura backendowa
- Tabela `settings` w bazie + model `Setting` z cache 1-dniowym
- Seeder z domyślnimi wartościami dla GTM, GA4, Pixel, Ads, Cookie Consent
- Panel admina Filament → **Settings → Tracking** — możliwość zmiany wszystkich ID bez dotykania kodu
- Middleware `HandleInertiaRequests` przekazuje ustawienia do frontu jako Inertia shared props

#### Google Tag Manager + GA4
- GTM snippet w app.blade.php ładowany warunkowo z bazy
- GTM Consent Mode v2 — domyślna odmowa (`ad_storage`, `analytics_storage` denied) **przed** załadowaniem GTM
- GA4 tag skonfigurowany w GTM (`G-QSMTFCBCDQ`) — zweryfikowany przez Tag Assistant
- Zdarzenie `generate_lead` wysyłane do GA4 przy submicie obu formularzy — **zweryfikowane w Google Analytics**

#### Meta Pixel
- Pixel ID `1455716816214418` zapisany w bazie, ładowany przez `window._metaPixelId`
- Piksel inicjalizuje się **tylko po akceptacji cookies marketingowych** (GDPR compliant)
- Zdarzenie `Lead` wysyłane przy submicie formularza kontaktowego i kalkulatora — **zweryfikowane przez Meta Pixel Helper**

#### Cookie Consent (GDPR)
- Hook `useConsent` z localStorage + wersjonowaniem zgód
- Komponent `<CookieBanner>` z granularnym wyborem (analityczne / marketingowe / preferencje)
- `ConsentContext` — dostęp do stanu zgody z dowolnego komponentu
- Przycisk **"Zarządzaj cookies"** w stopce

#### DataLayer / eventy
- Utility `pushEvent()` w `utils/dataLayer.js`
- `generate_lead` z parametrami `lead_source`, `project_type`, `estimate_low/high` — z obu formularzy

---

### Co jeszcze NIE jest skonfigurowane (poza zakresem sesji)

| Element | Status | Co zrobić |
|---|---|---|
| **Google Ads** | Infrastruktura gotowa, brak ID | Założyć konto Google Ads → wpisać `AW-XXXXXXXXX` w Filament → dodać tagi w GTM |
| **GTM Publish** | Zmiany w Preview, nie opublikowane | Kliknąć **Publish** w GTM → wersja produkcyjna |