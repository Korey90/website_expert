# HMRC MTD VAT — production readiness and security gate

**Owner:** Website Expert NI  
**Last review:** 2026-11-11  
**Current decision:** **NOT READY — production access and submission remain locked**

This document is the UKFIN-612 operational record. It is an internal security assessment and production-access checklist, not an independent penetration-test certificate and not HMRC approval.

## Fail-closed controls implemented

- `HMRC_ENVIRONMENT` accepts only `sandbox` or `production`.
- VAT, OAuth and application-token requests must use the exact official HMRC host for the selected environment. A configurable URL cannot be used to send credentials to another host.
- The production OAuth callback must be HTTPS, use the `APP_URL` host and have the exact path `/admin/hmrc/oauth/callback`.
- Production access requires both `HMRC_PRODUCTION_ACCESS_ENABLED=true` and a non-empty HMRC approval reference.
- Production submissions have a second, independent switch and additionally require references for FPH validation, security review and explicit owner approval.
- Any allowed missing fraud-prevention header requires an HMRC exception reference. Placeholder headers remain prohibited.
- `Gov-Test-Scenario` remains forbidden in production.
- OAuth uses one-time, tenant/user/environment-bound `state`, PKCE S256 and a time limit. Tokens and VRN remain encrypted at rest and are not rendered in the panel.
- POST has no automatic retry. A transport error or ambiguous result enters the existing GET-only reconciliation workflow.
- The readiness command reports only pass/fail requirements and never prints credentials, token values, VRN or fraud-header values.

Run the gate on the release artifact:

```bash
php artisan hmrc:production-readiness
php artisan hmrc:production-readiness --json
```

Any failed check returns exit code `1`. This command must be a deployment gate; exit code `0` is necessary but does not replace HMRC approval or the supervised pilot.

## Environment variables and activation order

Secrets must be supplied by the deployment secret store, never committed to Git, copied to this document or pasted into a ticket.

| Phase | Required values | Submission switch |
|---|---|---|
| Sandbox verification | sandbox client credentials, sandbox HTTPS callback, FPH product/vendor settings | production switches both `false` |
| HMRC approval received | production client credentials and callback, `HMRC_PRODUCTION_APPROVAL_REFERENCE` | both switches still `false` |
| Production read-only smoke test | `HMRC_ENVIRONMENT=production`, `HMRC_PRODUCTION_ACCESS_ENABLED=true` | `HMRC_PRODUCTION_SUBMISSION_ENABLED=false` |
| Supervised pilot | FPH, security-review and owner-approval references recorded | set submission to `true` only for the approved window |

Required production endpoints are fixed in code:

- API: `https://api.service.hmrc.gov.uk`
- OAuth authorize: `https://www.tax.service.gov.uk/oauth/authorize`
- OAuth token: `https://api.service.hmrc.gov.uk/oauth/token`

Do not enable production submission merely because credentials are present.

## Internal assessment results — 2026-11-11

| Area | Result | Evidence / remaining action |
|---|---|---|
| Environment separation and endpoint SSRF resistance | PASS | Exact-host guard covered by regression tests; a hostile base URL is rejected before HTTP. |
| Production kill switches | PASS | Read access and submission use separate fail-closed gates. |
| OAuth callback, state and PKCE | PASS | Callback host/path validation plus existing tampered-state, expiry and token-sanitisation tests. |
| Authentication, CSRF and throttling | PASS | HMRC controller routes require web session, auth, verified email, active business and `throttle:10,1`; state-changing routes use POST/DELETE under web CSRF middleware. |
| Tenant/RBAC isolation | PASS | Existing Finance/HMRC suites cover membership, permissions and cross-tenant denial. |
| Sensitive data in tracked source | PASS | High-confidence credential assignment scan returned no findings. |
| PHP dependency audit | PASS | 11 advisories remediated; `composer audit --locked` returns no advisories. |
| JavaScript dependency audit | PASS | DOMPurify is pinned above the affected range at `3.4.17`; postcss-selector-parser is overridden to patched `6.1.5`; build/test tooling advisories were also remediated. A clean install and full `npm audit --audit-level=low` return no vulnerabilities. |
| Independent penetration test | **PENDING** | Arrange an external authenticated assessment against a production-equivalent staging environment before production submission. |
| Fraud-prevention headers | **PENDING** | Obtain a real originating public source port through the edge or a written HMRC exception; configure the real vendor licence or obtain a written exception; rerun the HMRC validator. |
| HMRC production access | **PENDING** | Complete sandbox evidence, questionnaires, terms acceptance and receive production credentials/approval. |

Because any failed or pending row above is a release blocker, UKFIN-613 must not submit a real return yet.

## HMRC end-to-end evidence required

The HMRC VAT MTD end-to-end service guide requires a compliant fraud-prevention-header implementation, retrieval of obligations and submission of a VAT return in the sandbox. Every optional endpoint used by the application must also be tested. For this system that means:

1. Rerun the Test Fraud Prevention Headers API with all required headers accepted, or retain the written HMRC exception reference for every permitted omission.
2. In the sandbox, use the UI to retrieve obligations, view a return, retrieve liabilities/payments and execute the controlled submit workflow with the test organisation.
3. Retain only safe evidence: date/time, scenario, HTTP outcome, correlation ID and internal audit-chain result. Never store tokens, raw FPH values or the full VRN in the evidence pack.
4. Contact `SDSTeam@hmrc.gov.uk` within two weeks of completing testing. Supply the application identity and requested test evidence through the channel instructed by HMRC; do not put credentials in Git or general documentation.
5. Complete the two HMRC questionnaires, accept the terms of use and wait for production access. HMRC states that this stage normally takes about ten working days.
6. Record the approval reference in the secret store, run the readiness command, then enable read-only production access first.
7. Enable submission only for UKFIN-613: one company, one return, owner approval and accountant supervision, with a rollback/reconciliation operator present.

Official references:

- [VAT MTD end-to-end service guide](https://developer.service.hmrc.gov.uk/guides/vat-mtd-end-to-end-service-guide/)
- [Fraud prevention headers](https://developer.service.hmrc.gov.uk/guides/fraud-prevention/)
- [HMRC Developer Hub terms of use](https://developer.service.hmrc.gov.uk/api-documentation/docs/terms-of-use)

## Deployment checklist

- [x] JavaScript advisories are remediated; a clean `npm ci` and full `npm audit --audit-level=low` report no vulnerabilities.
- [x] `composer audit --locked` has no advisories.
- [ ] Independent authenticated penetration test has no unresolved high/critical finding.
- [ ] Production host has `APP_ENV=production`, `APP_DEBUG=false`, HTTPS and secure/encrypted/HTTP-only session cookies.
- [ ] Trusted proxies are explicit; real client IP and source port cannot be spoofed through untrusted forwarding headers.
- [ ] Vendor public IP and every TLS termination hop are the actual production values.
- [ ] Vendor licence is configured, or HMRC's written exception reference is configured.
- [ ] All sandbox `Gov-Test-Scenario` environment values are blank.
- [ ] Sandbox obligations, return, liabilities, payments and submission evidence is complete.
- [ ] HMRC questionnaires and terms are complete; production approval has been received.
- [ ] Production OAuth callback is registered exactly and production credentials are in the secret store.
- [ ] Backup, restore, queue monitoring, alerting and audit-log retention have been rehearsed.
- [ ] `php artisan hmrc:production-readiness` returns exit code `0` on the release artifact.
- [ ] Read-only production smoke test passes with submission still disabled.
- [ ] Accountant and owner approve the exact UKFIN-613 pilot return and window.

## Emergency stop

Set `HMRC_PRODUCTION_SUBMISSION_ENABLED=false`, clear configuration cache and restart workers. If all HMRC traffic must stop, also set `HMRC_PRODUCTION_ACCESS_ENABLED=false`. Preserve submission/audit records; do not delete or retry an ambiguous attempt. Use the existing GET-only reconciliation path after the incident is understood.
