# Website Expert AI — Sprints 02 / 03 / 04 (coding only)

Data: 2026-11-24. Branch: feature/website-expert-ai-sprints-02-03.
Base: feature/website-expert-ai-sprint-01 (Sprint 01 is still untested).

## Status

The implementation is **code-complete for the core Sprint 02 and Sprint 03 paths**, with Sprint 04 privacy/analytics groundwork. No runtime tests were run, no migrations were executed, no API charges incurred, and no deployment/merge occurred. Local review is mandatory.

### Sprint 02 — Public AI chat widget

- React + TypeScript widget in resources/js/Components/AI/PublicAiWidget.tsx.
- Lazy-mounted within resources/js/Layouts/MarketingLayout.jsx, so marketing pages share one widget.
- The browser first requests GET /ai/bootstrap and receives disabled by default.
- Same-origin web routes with session and CSRF: GET /ai/bootstrap, GET /ai/history, POST /ai/message, POST /ai/restart.
- No anonymous caller-provided business_id, assistant_id or arbitrary backend URL. The backend reads an explicit AI_ASSISTANT_PUBLIC_BUSINESS_ID ULID, validates the tenant is active and the assistant is enabled.
- Message IDempotency via client_message_id UUID; same-session conversation binding with HMAC session fingerprint.
- Encrypted messages and unanswered questions at rest using Laravel's encrypted Eloquent casts.
- Retrieval only from approved/published RAG chunks. The answerer validates every exact evidence quote against the source. An unknown question fails closed and is logged for manual review.
- Language labels and messages in EN, PL and PT; no browser-side API key.
- Rate limits: 6 messages/min per IP, 30 history/bootstrap/min, 5 restart/min.
- Server-side reCAPTCHA v3 is fail-closed for public POSTs. It verifies action, hostname and score. The existing permissive RecaptchaService is not used.
- Same-origin integration, NOT an embeddable third-party SaaS widget. External multi-tenant embed is postponed to Sprint 05.

### Sprint 03 — CRM & learning

- Explicit opt-in enquiry form in the widget, POST /ai/lead (2/min per IP), FormRequest validation, honeypot field.
- Uses existing CreateLeadAction; records source=other (MySQL enum compatible) and source_type=ai_chat.
- Stores only the explicitly submitted project brief, NOT the whole conversation, in CRM.
- Adds existing LeadConsent audit with version, language, timestamp and HMAC IP hash; new ai_leads connects conversation and CRM lead.
- Filament pages under Settings: AI Conversations (read-only), AI Learning (manual review) and existing AI Knowledge.
- No automatic training or publication of unanswered questions.
- Separate permission keys; manager/developer blocked by default, admin granted after AdminSeeder is run.

### Sprint 04 — privacy and operations groundwork

- New retention command ai:prune-conversations, scheduled daily.
- Authorized admin can erase transcripts manually. With an associated lead, the CRM consent link is preserved.
- Optional Docker AI queue worker uses profile ai; it is not started by default. Disabled unless AI_ASSISTANT_RETENTION_ENABLED=true.
- After N days, delete conversations without a CRM lead. Conversations with a lead retain the consent linkage but delete transcript, unanswered questions and session fingerprint.
- Analytics dashboard: grounded/unknown/unavailable counts, open questions, leads, provider calls, reserved maximum cost (NOT provider invoice).
- PublicAssistantResolver will refuse to activate if retention is disabled.
- Conservative API budget reservation and rate limiting remain mandatory.
- This is NOT a production-readiness certification: security review, vendor DPA/privacy copy, load testing and runtime verification still required.

## Safe local environment

Make a local checkout, install dependencies, copy .env.example to .env and run a separate test database.

Public flags default to OFF. To test private knowledge, use AI_ASSISTANT_PREVIEW_ENABLED=true, AI_ASSISTANT_DAILY_CALLS > 0, AI_ASSISTANT_DAILY_BUDGET_MICROUSD > 0, AI_ASSISTANT_RESERVATION_MICROUSD > 0 and a development-only OPENAI_API_KEY.

For public widget testing in local staging:
- AI_ASSISTANT_ENABLED=true
- AI_ASSISTANT_RETENTION_ENABLED=true
- AI_ASSISTANT_RETENTION_DAYS=30
- AI_ASSISTANT_RECAPTCHA_REQUIRED=true (recommended for production)
- Configure RECAPTCHA_SITE_KEY, RECAPTCHA_SECRET_KEY, and VITE_RECAPTCHA_SITE_KEY with the corresponding v3 site key. All keys must belong to the same site and hostname.
- For offline local testing only: AI_ASSISTANT_RECAPTCHA_REQUIRED=false, never use this shortcut for real public traffic.
- AI_ASSISTANT_PUBLIC_BUSINESS_ID=<explicit ULID of approved agency tenant>
- For the target tenant set ai_assistants.enabled=true via authorized Filament page.
- Publish approved public-only knowledge, ensure ai queue worker consumes jobs and embeddings have compatible configured model/dimensions.
- Verify consent disclosure and privacy policy have been reviewed before public use.
- Require site HTTPS, secure cookie, CAPTCHA/bot protection plan and server-side cost caps before real traffic.

Do not set AI_ASSISTANT_PUBLIC_BUSINESS_ID to the first active Business automatically. Never use defaultBusiness() here.

## Local verification (not executed in ChatGPT environment)

Run migrations against isolated SQLite AND MySQL 8 test databases, then:
- php artisan test tests/Unit/AI tests/Feature/AI
- php artisan test tests/Feature/TenantIsolationTest.php tests/Feature/Filament/FilamentPermissionAccessTest.php
- php artisan test
- vendor/bin/pint --test
- npm run build
- npm run lint
- npm test
- php artisan view:cache
- php artisan route:list --path=ai
- php artisan ai:prune-conversations --dry-run (retention flag true)
- Manual strict reCAPTCHA test: wrong action, invalid score, wrong hostname, API failure and missing token must be rejected; only correct v3 token succeeds.
- Manual CSRF checks: invalid/absent token must give 419; outsider must not change business_id; unauthenticated visitors cannot access any Filament AI page.
- Manual prompt injection tests: user prompt and retrieved documents cannot override evidence validation; only literal approved excerpts may appear.
- Manual accessibility check: responsive layout/mobile/keyboard/Escape/aria, EN/PL/PT translations.
- Manual CRM test for correct source_type, consent log and duplicate lead submission.
- Manually confirm no OpenAI cost when widget or preview is disabled.

**Production deployment explicitly out of scope.** Branch remains separate from main.

## Known limitations / follow-ups

- Strict evidence quotes are grounded, but not yet fluent paraphrases. Natural-language claim verification needs evaluation before enabling creative generation.
- The widget is same-origin only. SaaS third-party embedding, per-site public widget tokens, allowlisted domains, tenant-specific API budgets, cross-origin cookie handling and client billing belong to Sprint 05.
- Rate limiting, cost caps and strict reCAPTCHA are implemented, but production abuse monitoring and incident response still require a live test and security review.
- MySQL schema and all PHP/TS/Blade changes need actual local runtime tests.
- Consent and retention are technical controls and do not replace legal review of the live privacy policy.
