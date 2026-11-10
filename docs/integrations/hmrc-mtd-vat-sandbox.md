# HMRC MTD VAT — sandbox setup

**Status:** OAuth implementation ready; external sandbox configuration and live sandbox journey pending
**Verified against official HMRC documentation:** 10 November 2026
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

- [ ] Subscribe the sandbox application to the three APIs above.
- [ ] Add redirect URI: `<APP_URL>/admin/hmrc/oauth/callback`.
- [ ] Copy the sandbox Client ID and Client Secret into local/server environment variables, never into Git or chat:
  - `HMRC_SANDBOX_CLIENT_ID`
  - `HMRC_SANDBOX_CLIENT_SECRET`
  - `HMRC_SANDBOX_REDIRECT_URI`
- [ ] Configure the real fraud-prevention infrastructure values:
  - `HMRC_PRODUCT_VERSION` — the deployed Website Expert NI release identifier.
  - `HMRC_VENDOR_PUBLIC_IP` — public IP reached by the originating browser.
  - `HMRC_VENDOR_FORWARDING_CHAIN` — every in-scope public TLS termination IP, in client-to-HMRC order; for a single server it is the same value as `HMRC_VENDOR_PUBLIC_IP`.
  - `HMRC_VENDOR_LICENSE_ID` — only when a genuine installation/software licence identifier exists; the system sends only its SHA-256 hash.
- [ ] Confirm the reverse proxy/WAF supplies the real originating public IP and source TCP port. If Laravel's direct request values are not the originating values, configure `HMRC_CLIENT_PUBLIC_IP_HEADER` and `HMRC_CLIENT_PUBLIC_PORT_HEADER`. The trusted edge must strip client-supplied versions and overwrite both headers. `X-Forwarded-Port: 443` is a destination port and must not be substituted for the source port.
- [ ] Set up authenticator-app MFA for the owner/admin who will validate and initiate HMRC operations.
- [ ] If a genuinely required value cannot be collected, contact HMRC first. Only after HMRC accepts the exceptional case may its exact header name be listed in `HMRC_FPH_ALLOWED_MISSING_HEADERS`; never use `null`, `undefined` or another placeholder.
- [ ] Keep `HMRC_ENVIRONMENT=sandbox` until the complete sandbox and security gates pass.
- [ ] Create or reuse an **organisation** test user and retain its test User ID, password and VRN in a password manager.
- [ ] Complete OAuth using the test user's HMRC credentials.
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

No live sandbox authorization has been performed yet. It requires the three API subscriptions, environment-only sandbox Client ID/Secret/redirect URI and an organisation test user.

## Implemented fraud prevention headers

The application implements HMRC's `WEB_APP_VIA_SERVER` connection method and builds all 16 currently required headers:

- browser JavaScript user agent, persistent device UUID, screen details, window size and UTC offset;
- originating public IP, collection timestamp and source TCP port;
- authenticated application user identifiers and a real TOTP event with a stable, non-secret factor reference;
- vendor product name/version, public IP, TLS forwarding chain and a SHA-256 hashed licence identifier.

The browser values are refreshed into a same-site cookie and strictly validated by the server. They are telemetry only and are never trusted for authentication or tenant authorisation. Full header values are not persisted or displayed. VAT API calls fail before HTTP when a required header is missing, malformed by the builder, or contains a line break.

At `/admin/hmrc-connection`, an owner/admin can choose **Validate fraud headers**, enter a current authenticator code and call `GET /test/fraud-prevention-headers/validate`. The system obtains a separate application-restricted access token using Client Credentials Grant. It can also retrieve `GET /test/fraud-prevention-headers/vat-mtd/validation-feedback`. Both operations are hard-blocked outside sandbox.

The local implementation and mocked contract tests are complete. A real HMRC validation remains pending until the external checklist above is complete. Passing the Test API is feedback on formatting and values, not a guarantee of production compliance.

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

## Official references

- [VAT (MTD) API](https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/vat-api/1.0)
- [VAT (MTD) end-to-end service guide](https://developer.service.hmrc.gov.uk/guides/vat-mtd-end-to-end-service-guide/)
- [Create Test User API](https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/api-platform-test-user/1.0)
- [Test Fraud Prevention Headers API](https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/txm-fph-validator-api/1.0)
- [OAuth user-restricted endpoints](https://developer.service.hmrc.gov.uk/api-documentation/docs/authorisation/user-restricted-endpoints)
- [Application-restricted endpoints](https://developer.service.hmrc.gov.uk/api-documentation/docs/authorisation/application-restricted-endpoints)
- [WEB_APP_VIA_SERVER headers](https://developer.service.hmrc.gov.uk/guides/fraud-prevention/connection-method/web-app-via-server/)
- [Fraud prevention Test API guidance](https://developer.service.hmrc.gov.uk/guides/fraud-prevention/test-api/)
