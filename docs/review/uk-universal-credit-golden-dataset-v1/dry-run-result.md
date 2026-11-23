# Universal Credit golden dataset v1 - isolated dry-run result

- Run date: 2026-11-23
- Environment: local automated test database with `RefreshDatabase` and fake private storage
- Fixture SHA-256: `ef92a67e1c458f001334f4659f9dd87bc54986cd2660c3fd757dd3caa6f0436a`
- Specialist review: accepted on paper, record `UC-GOLDEN-V1-PAPER-20261124`

## Result

Automated dry run: PASS

The test performed the full isolated workflow:

1. created a dedicated sole-trader tenant;
2. enabled the Universal Credit profile;
3. created the exact 31-day assessment period;
4. added and classified five included and one excluded cash events;
5. added mileage and home-working simplified expenses;
6. calculated GBP 1,290.00 income, GBP 199.00 expenses and GBP 1,091.00 net income;
7. received amber preflight because the fixture intentionally uses synthetic missing-evidence explanations;
8. confirmed warnings, created the immutable review snapshot and locked the period;
9. generated a completed evidence ZIP with PDF, XLSX, CSV, policy, reconciliation and SHA-256 manifest;
10. marked the period as manually reported using reference `GOLDEN-V1-DRY-RUN`;
11. completed the finance tenant audit with no invalid records.

Automated assertion result: 1 test passed, 38 assertions.

## Specialist review artifact

The same isolated test can export the ZIP produced by the application generator to:

`docs/demo/uk-universal-credit-golden-v1/universal-credit-golden-v1.zip`

Its dataset hash, review snapshot hash, ZIP hash and size are recorded in:

`docs/demo/uk-universal-credit-golden-v1/artifact.sha256.json`

## Re-run command

```powershell
php artisan test tests/Feature/Finance/UniversalCreditGoldenDatasetTest.php
```

To regenerate the committed specialist review artifact:

```powershell
$env:GENERATE_UC_GOLDEN_PACK = '1'
php artisan test tests/Feature/Finance/UniversalCreditGoldenDatasetTest.php
Remove-Item Env:GENERATE_UC_GOLDEN_PACK
```

## Specialist decision and production gate

The specialist review of the exact fixture and review ZIP was accepted on paper and recorded in `specialist-sign-off-record.md` and `specialist-sign-off.json`.

On 24 November 2026, the project owner confirmed that the accountant also compared the cases with the current Universal Credit account task or form and found no issue. The validation gate for the exact fixture and ZIP is accepted.

A new fixture version and a new specialist decision are required if any expected value, classification, calculation policy or relevant official UC workflow changes.
