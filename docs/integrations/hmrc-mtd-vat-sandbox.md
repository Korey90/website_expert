# HMRC MTD VAT — sandbox setup

**Status:** OAuth and sandbox VRN connected; obligations/read/submission and redacted evidence export ready; live VAT calls blocked by the fraud-header infrastructure gate
**Verified against official HMRC documentation:** 11 November 2026
**Scope:** Website Expert NI, direct business journey, VAT MTD only

## APIs to add to the sandbox application

Add these three APIs in HMRC Developer Hub → Applications → your sandbox application → Add an API:

1. **VAT (MTD) API — version 1.0**
   - Required for VAT obligations, submitting and viewing VAT returns, VAT liabilities and VAT payments.
   - OAuth scopes used by the application: `read:vat` and `write:vat`.
   - Sandbox base URL: `https://test-api.service.hmrc.gov.uk`.
2. **Create Test User API — version 1.0**
   - Sandbox-only support API used to create an organisation test user with Government Gateway credentials and a test VRN.
3. **Test Fraud Prevention Headers API — version 1.0**
   - Sandbox-only API used to validate the mandatory `Gov-*` headers and retrieve feedback for `vat-mtd` calls.

The following are **not required** for the first end-to-end VAT sandbox journey:

- VAT Assist (MTD) — separate alpha/assisted-feedback flow, not needed to file a VAT return.
- Agent Authorisation API — not needed for the direct Website Expert NI business journey. Reassess before supporting accountancy practices acting for clients.
- MTD Income Tax, Corporation Tax, PAYE and CIS APIs — separate future workstreams outside VAT MVP.

## Sandbox application checklist

- [x] Subscribe the sandbox application to the three APIs above.
- [x] Add redirect URI: `<APP_URL>/admin/hmrc/oauth/callback`.
- [x] Copy the sandbox Client ID and Client Secret into local/server environment variables, never into Git or chat:
  - `HMRC_SANDBOX_CLIENT_ID`
  - `HMRC_SANDBOX_CLIENT_SECRET`
  - `HMRC_SANDBOX_REDIRECT_URI`
- [ ] Configure the real fraud-prevention infrastructure values:
  - `HMRC_PRODUCT_VERSION` — the deployed Website Expert NI release identifier.
  - `HMRC_VENDOR_PUBLIC_IP` — public IP reached by the originating browser.
  - `HMRC_VENDOR_FORWARDING_CHAIN` — every in-scope public TLS termination IP, in client-to-HMRC order; for a single server it is the same value as `HMRC_VENDOR_PUBLIC_IP`.
  - `HMRC_VENDOR_LICENSE_ID` — only when a genuine installation/software licence identifier exists; the system sends only its SHA-256 hash.
- [ ] Confirm the reverse proxy/WAF supplies the real originating public IP and source TCP port. If Laravel's direct request values are not the originating values, configure `HMRC_CLIENT_PUBLIC_IP_HEADER` and `HMRC_CLIENT_PUBLIC_PORT_HEADER`. The trusted edge must strip client-supplied versions and overwrite both headers. `X-Forwarded-Port: 443` is a destination port and must not be substituted for the source port.
- [x] Set up authenticator-app MFA for the owner/admin who will validate and initiate HMRC operations.
- [ ] If a genuinely required value cannot be collected, contact HMRC first. Only after HMRC accepts the exceptional case may its exact header name be listed in `HMRC_FPH_ALLOWED_MISSING_HEADERS`; never use `null`, `undefined` or another placeholder.
- [ ] Keep `HMRC_ENVIRONMENT=sandbox` until the complete sandbox and security gates pass.
- [x] Create or reuse an **organisation** test user and retain its test User ID, password and VRN in a password manager.
- [x] Complete OAuth using the test user's HMRC credentials.
- [ ] Run obligations → submit open period → view return → liabilities → payments.
- [ ] Validate every endpoint's fraud-prevention headers and retrieve `vat-mtd` validation feedback.

Do not send HMRC credentials, Client Secret, access tokens, refresh tokens or full VRNs through project logs, screenshots, commits or chat.

## Implemented OAuth connection

The tenant owner or administrator manages the connection at `/admin/hmrc-connection`.

- OAuth 2.0 Authorization Code Grant uses a cryptographically random, one-time `state` and PKCE S256.
- The state is bound to the active tenant, user and HMRC environment and expires after the configured TTL.
- The Client Secret is sent only to the token endpoint and never appears in the authorization URL.
- Access tokens, rotating single-use refresh tokens and the VRN are encrypted at rest. The UI never displays tokens or the full VRN.
- Token refresh locks the connection row to prevent concurrent reuse of a single-use refresh token.
- `invalid_grant` clears local credentials and marks the connection as requiring reconnection.
- Application errors are intentionally sanitized; raw HMRC bodies, authorization codes, tokens and secrets are not logged or shown.

The application's **Disconnect** action only deletes the locally stored credentials. It does not claim to revoke the user's authorization at HMRC because HMRC does not document a programmatic revoke endpoint for this flow. To withdraw the authority at HMRC, the user must also remove the application through HMRC's **Manage authorised applications** service.

Live sandbox OAuth has been completed and the test VRN is stored encrypted. The application UI exposes only connection state and the final four VRN digits.

## Implemented fraud prevention headers

The application implements HMRC's `WEB_APP_VIA_SERVER` connection method and builds all 16 currently required headers:

- browser JavaScript user agent, persistent device UUID, screen details, window size and UTC offset;
- originating public IP, collection timestamp and source TCP port;
- authenticated application user identifiers and a real TOTP event with a stable, non-secret factor reference;
- vendor product name/version, public IP, TLS forwarding chain and a SHA-256 hashed licence identifier.

The browser values are refreshed into a same-site cookie and strictly validated by the server. They are telemetry only and are never trusted for authentication or tenant authorisation. Full header values are not persisted or displayed. VAT API calls fail before HTTP when a required header is missing, malformed by the builder, or contains a line break.

At `/admin/hmrc-connection`, an owner/admin can choose **Validate fraud headers**, enter a current authenticator code and call `GET /test/fraud-prevention-headers/validate`. The system obtains a separate application-restricted access token using Client Credentials Grant. It can also retrieve `GET /test/fraud-prevention-headers/vat-mtd/validation-feedback`. Both operations are hard-blocked outside sandbox.

The panel now exposes **Retrieve fraud-header feedback** as a separate sandbox-only action. It validates the feedback envelope and displays only the number of assessed requests, timestamp and a safe correlation ID. Raw feedback, header values and tokens are not displayed or persisted.

The local implementation and mocked contract tests are complete. The real Test API currently reports one error (`Gov-Client-Public-Port`) and one warning (`Gov-Vendor-License-IDs`). zrok does not expose the originating browser source TCP port; this must be solved by suitable trusted-edge infrastructure or an explicit HMRC-approved exception. Passing the Test API is feedback on formatting and values, not a guarantee of production compliance.

## Redacted sandbox evidence

Successful panel operations append tenant-owned evidence events for fraud-header validation/feedback, obligations, VAT Return reads, liabilities and payments. The events are immutable and form a per-business SHA-256 chain. They retain only the operation, outcome, time, actor pseudonym, safe correlation ID and bounded counts; they never retain tokens, full VRN, period keys, browser telemetry, raw headers, response bodies or financial amounts.

Submission evidence remains in its existing, independent append-only submission chain. Export verifies both chains and emits a redacted JSON document plus a `.sha256` sidecar:

```bash
php artisan hmrc:export-sandbox-evidence website-expert
```

The default private output directory is `storage/app/private/hmrc-evidence`. The export is sandbox-only and includes the Website Expert NI name, pseudonymous business/actor references, connection/scopes status, obligation counts, operation evidence and verified submission hashes. Migration `2026_11_11_000032_create_hmrc_sandbox_evidence_events_table.php` creates the tenant evidence store.

## Planned API operations

| Operation | Method and path | OAuth scope |
|---|---|---|
| Retrieve obligations | `GET /organisations/vat/{vrn}/obligations` | `read:vat` |
| Submit VAT return | `POST /organisations/vat/{vrn}/returns` | `write:vat` |
| View VAT return | `GET /organisations/vat/{vrn}/returns/{periodKey}` | `read:vat` |
| Retrieve liabilities | `GET /organisations/vat/{vrn}/liabilities` | `read:vat` |
| Retrieve payments | `GET /organisations/vat/{vrn}/payments` | `read:vat` |
| Validate fraud headers | `GET /test/fraud-prevention-headers/validate` | application-restricted token |
| Get fraud-header feedback | `GET /test/fraud-prevention-headers/vat-mtd/validation-feedback` | application-restricted token |

HMRC requires `Accept: application/vnd.hmrc.1.0+json`. The application uses OAuth 2.0 Authorization Code Grant with PKCE S256. Authorization codes are single-use and valid for 10 minutes, access tokens last four hours, and refresh tokens are single-use; token refresh is therefore protected against races.

## Implemented VAT-obligation synchronisation

An owner or administrator can use **Sync VAT obligations** at `/admin/hmrc-connection`. The action:

- requires a current authenticator-app code and sends the complete fraud-prevention header set;
- refreshes an access token that is expired or close to expiry before the VAT API call;
- retrieves open, fulfilled or both kinds of obligation over a maximum 366-day range;
- strictly validates the full response before writing anything, then idempotently creates or updates tenant-owned obligation records;
- retains obligations that are absent from a later filtered response, because absence is not evidence that HMRC deleted the historical obligation;
- records first/last seen timestamps, status changes, the initiating user and the HMRC correlation ID without exposing tokens or the full VRN;
- keeps HMRC VAT obligations separate from `accounting_periods`, which control local ledger closing and locking;
- stores the technical HMRC `periodKey` for future view/submit operations but deliberately never displays it to the user.

Special period keys containing `#` are accepted and percent-encoded when used in an HMRC URL. An invalid or duplicate response, a missing `read:vat` scope or an inactive/non-owner membership fails without partial period changes.

For fixed sandbox responses, `HMRC_SANDBOX_OBLIGATIONS_SCENARIO`, `HMRC_SANDBOX_RETURN_SCENARIO`, `HMRC_SANDBOX_LIABILITIES_SCENARIO`, `HMRC_SANDBOX_PAYMENTS_SCENARIO` and `HMRC_SANDBOX_SUBMISSION_SCENARIO` may optionally be set to endpoint-specific HMRC `Gov-Test-Scenario` values. Submission values are fail-closed to the endpoint's documented catalogue: `INVALID_VRN`, `INVALID_PERIODKEY`, `INVALID_PAYLOAD`, `DUPLICATE_SUBMISSION`, `TAX_PERIOD_NOT_ENDED` and `INSOLVENT_TRADER`. Leave the submission value blank for the successful default and never set a test scenario in production.

The local MySQL migration is `2026_11_10_000027_create_hmrc_vat_obligations_table.php`. Mocked integration coverage is complete; the real obligations call remains pending until the external sandbox checklist is complete.

## Implemented VAT Return submission

The locked VAT Return detail page exposes **Submit to HMRC** only to an active tenant member with the separate `submit_tax_returns` permission. Submission additionally requires:

- a matching open HMRC obligation for the exact local return period;
- an integrity-valid immutable snapshot containing the canonical nine HMRC fields;
- the exact HMRC business legal declaration, explicitly accepted by the user;
- a newly verified authenticator-app code and a complete fraud-prevention header set;
- a connected sandbox/production account with `write:vat` and a valid encrypted VRN.

The POST payload is built only from the locked snapshot, uses the obligation's hidden canonical `periodKey` and always sends `finalised: true`. Before the network request, the system persists a tenant-owned attempt with snapshot/payload hashes, declaration version, actor and hashed MFA reference. A successful HTTP 201 receipt is schema-validated; its form bundle and charge reference are encrypted at rest.

There is no blind POST retry. A timeout, HMRC 5xx, non-201 success response or malformed receipt marks the attempt as `outcome_unknown`, blocks another submission and permits only the GET-based reconciliation described below. A definitive 4xx response is recorded as `rejected` with only its sanitised code, status and correlation ID. After its cause is corrected, a non-duplicate rejection can be submitted again only through a new explicit declaration and fresh MFA; the same audit record is re-prepared and retains the complete event chain. A duplicate rejection is special: the current POST was rejected, but because a remote return already exists it permits only GET-based reconciliation and never another POST. The local migration is `2026_11_11_000029_create_hmrc_vat_submissions_table.php`.

## Implemented unknown-outcome reconciliation

For an `outcome_unknown` attempt, or a `DUPLICATE_SUBMISSION` rejection, the VAT Return page exposes **Check HMRC status** after a fresh authenticator code. Reconciliation performs only safe GET requests and never repeats the submission POST:

1. retrieve the obligation for the exact locked return period;
2. if it remains open, retain `outcome_unknown` and record `pending_open`;
3. if it is fulfilled, retrieve the submitted return using the hidden canonical period key;
4. compare all nine HMRC fields with the immutable snapshot, normalising Boxes 1–5 to pennies and Boxes 6–9 to whole pounds;
5. record either `matched`/submitted or an explicit `outcome_conflict` requiring manual investigation.

Every check records its actor, time, count, HMRC received date where available, sanitized error metadata and a redacted correlation ID. Snapshot and pre-POST payload hashes are revalidated before any HMRC call. Invalid responses and GET failures remain safe to check again; conflicts and confirmed matches cannot be reconciled repeatedly. Migration `2026_11_11_000030_add_reconciliation_to_hmrc_vat_submissions_table.php` adds the audit state.

## Implemented audit trail and receipt PDF

Every direct submission and reconciliation transition appends an immutable tenant-owned event. Events contain only allow-listed, length-limited metadata and form a SHA-256 chain using their sequence, previous hash, actor, timestamp, state and canonical metadata. Updating or deleting an event through the domain model is prohibited; receipt generation verifies the complete chain before rendering. The audit trail never stores an access/refresh token, full VRN, MFA secret, hidden period key or raw HMRC response.

After a direct successful HTTP 201 submission with a complete HMRC receipt, an authorised user with `submit_tax_returns` can download a private, `no-store` PDF from the VAT Return page. The PDF contains the form bundle, optional charge reference, processing time, immutable snapshot/payload hashes and verified audit events. It is clearly labelled as system-generated evidence of the HMRC API response, not an HMRC-issued document. A return confirmed later only by GET reconciliation cannot generate a substitute receipt because HMRC does not return the original form bundle through that path.

Each generated PDF is SHA-256 hashed and adds a `receipt_generated` event. Migration `2026_11_11_000031_create_hmrc_vat_submission_events_table.php` creates the append-only event store.

## Sandbox scenario matrix

The UKFIN-611 automated contract matrix runs with outbound HTTP disabled. It exercises the same application action, OAuth refresh, HTTP client, persistence, policy and append-only audit path used by the UI:

| Scenario | Sandbox input | Required local result |
|---|---|---|
| Success | no `Gov-Test-Scenario` | one POST, validated HTTP 201 receipt, `submitted` |
| Invalid VRN | `INVALID_VRN` | sanitised 4xx, `rejected`, no blind retry |
| Expired access token | locally expired token | atomic refresh-token rotation, then exactly one submission POST |
| Duplicate | `DUPLICATE_SUBMISSION` | `rejected`, then GET-only reconciliation is available |
| HMRC 5xx | deterministic HTTP contract simulation | `outcome_unknown`, no retry, reconciliation required |
| Timeout | deterministic connection-failure simulation | `outcome_unknown`, no retry, no transport details persisted |

HMRC documents that not every failure can be generated with `Gov-Test-Scenario`; therefore 5xx and timeout are transport simulations rather than invented header values. The live success/4xx runs remain intentionally blocked until the real originating source port and licence-header position pass the Fraud Prevention Headers gate. No real VAT Return was submitted while completing this matrix.

## Production security gate

Production is protected by separate read-access and submission switches, exact HMRC endpoint/callback validation and approval-reference gates. The release procedure, security findings, HMRC approval sequence and emergency stop are maintained in [HMRC production readiness and security gate](hmrc-production-readiness.md). A failed readiness check blocks production; sandbox credentials or a successful OAuth connection are never sufficient to unlock it.

## Official references

- [VAT (MTD) API](https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/vat-api/1.0)
- [VAT (MTD) end-to-end service guide](https://developer.service.hmrc.gov.uk/guides/vat-mtd-end-to-end-service-guide/)
- [Create Test User API](https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/api-platform-test-user/1.0)
- [Test Fraud Prevention Headers API](https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/txm-fph-validator-api/1.0)
- [OAuth user-restricted endpoints](https://developer.service.hmrc.gov.uk/api-documentation/docs/authorisation/user-restricted-endpoints)
- [Application-restricted endpoints](https://developer.service.hmrc.gov.uk/api-documentation/docs/authorisation/application-restricted-endpoints)
- [WEB_APP_VIA_SERVER headers](https://developer.service.hmrc.gov.uk/guides/fraud-prevention/connection-method/web-app-via-server/)
- [Fraud prevention Test API guidance](https://developer.service.hmrc.gov.uk/guides/fraud-prevention/test-api/)
