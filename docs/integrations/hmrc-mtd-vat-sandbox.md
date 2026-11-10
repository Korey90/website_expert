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
