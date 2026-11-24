# AI Local Validation Checklist (Sprints 01–04)

**Owner action required:** all code was written through the GitHub connector without executing the application. Keep AI_ASSISTANT_ENABLED=false until every mandatory item below passes.

## 1. Environment and schema

- [ ] In an isolated checkout, verify PHP extensions, Composer lock and vendor installation.
- [ ] Confirm migration order, foreign keys and ULID string width on SQLite and MySQL 8.0.
- [ ] Confirm ai_messages.content and ai_unanswered_questions.question are encrypted in the DB, and decrypted only through authorized Eloquent properties.
- [ ] Run AdminSeeder locally, inspect Spatie permissions: admin may view/manage AI; manager/developer/client do not get AI permissions by default.
- [ ] Confirm no new database tables or models conflict with pre-existing modules.
- [ ] Review local .env / CSRF / Secure+HttpOnly+SameSite session cookie configuration.

## 2. Backend validation (without production secrets)

\`\`\`bash
composer install
php artisan migrate:status
php artisan test tests/Unit/AI tests/Feature/AI
php artisan test tests/Feature/TenantIsolationTest.php tests/Feature/Filament/FilamentPermissionAccessTest.php
php artisan test
vendor/bin/pint --test
php artisan route:list --path=ai
php artisan view:cache
\`\`\`

All commands should use a separate test database. Do not run migrate:fresh on production. If branch builds after a merged main change, inspect migration date conflicts.

## 3. Frontend validation

\`\`\`bash
npm ci
npm run build
npm run lint
npm test
\`\`\`

Check homepage, contact, blog, services and Polish landing pages for regressions. On narrow screens ensure the fixed widget fits, does not hide important buttons, keyboard navigation works, Escape closes the panel, message text is escaped and there are no console errors.

## 4. Public feature flags and data isolation

- [ ] With AI_ASSISTANT_ENABLED=false, GET /ai/bootstrap returns enabled:false and widget stays hidden.
- [ ] With AI_ASSISTANT_RETENTION_ENABLED=false, the resolver refuses public traffic.
- [ ] Without valid AI_ASSISTANT_PUBLIC_BUSINESS_ID, the resolver refuses public traffic.
- [ ] Disable the tenant's ai_assistants.enabled; public traffic must be refused.
- [ ] Passing another business_id or assistant_id in a request must NOT change the server-selected tenant.
- [ ] Test session ownership: another browser must never read another visitor's conversation.
- [ ] Confirm POST requests require CSRF and are rate limited.
- [ ] With strict reCAPTCHA required, invalid token/action/host/score must fail closed and cannot incur OpenAI cost.
- [ ] With no approved documents, the bot responds "unknown" without model generation and logs question for manual review.
- [ ] With approved, published and indexed knowledge only, responses carry correct source document/version and literal evidence.
- [ ] Prompt injection from user / untrusted text cannot cause extra actions, private data retrieval or unsupported claims.
- [ ] Test multiple concurrent submissions and reconnect during slow OpenAI responses.
- [ ] Test budgets and disabled key/model configuration returning graceful errors without raw provider details.

## 5. Lead consent and CRM

- [ ] Lead submission without accepted consent yields 422; no lead is created.
- [ ] Valid lead uses source='other', source_type='ai_chat'; no MySQL ENUM migration required.
- [ ] Existing CreateLeadAction creates Client/Contact/Lead in proper business and does not cross-tenant dedupe.
- [ ] Exactly one ai_leads mapping and LeadConsent evidence is recorded per conversation.
- [ ] Full chat history is never copied into a CRM lead; only explicitly submitted project description is included.
- [ ] Verify reCAPTCHA verification on lead uses the different action website_ai_lead.

## 6. Dashboard, learning and retention

- [ ] Check AI Knowledge, AI Conversations, AI Learning and AI Analytics for admin; none for unauthorized role.
- [ ] Confirm draft / approve / queued indexing / active version / rollback behavior; no automatic learning.
- [ ] Verify messages for tenant A cannot be viewed by admin of tenant B.
- [ ] Test manual transcript erasure with and without linked CRM lead.
- [ ] Run php artisan ai:prune-conversations --dry-run on isolated staging data.
- [ ] Enable retention locally and verify expired anonymous conversations delete, while lead consent link survives and transcript is erased.
- [ ] Confirm scheduled command does nothing when AI_ASSISTANT_RETENTION_ENABLED=false.
- [ ] Verify AI Analytics displays *reserved budget*, not actual billed costs.

## 7. Before production

- [ ] Independent code review, privacy-policy update, security review, API key and billing limits.
- [ ] Reconcile OpenAI provider processing and Data Processing Agreement requirements.
- [ ] Configure worker for ai queue (optional development Docker profile), monitoring, logging and backup.
- [ ] Keep deploy and main merge behind explicit owner approval.

Sprint 05 (third-party SaaS embedding) is not included in these commits. Do not advertise it as implemented.
