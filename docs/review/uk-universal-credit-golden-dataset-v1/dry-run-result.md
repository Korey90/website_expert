# Universal Credit golden dataset v1 - isolated dry-run result

- Run date: 2026-11-23
- Environment: local automated test database with `RefreshDatabase` and fake private storage
- Fixture SHA-256: `ef92a67e1c458f001334f4659f9dd87bc54986cd2660c3fd757dd3caa6f0436a`
- Specialist review: pending

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

## Remaining production gate

This successful technical dry run is not professional approval of Universal Credit treatment. Production acceptance still requires:

- a specialist to complete one of the review checklists in this directory;
- comparison with the current official Universal Credit form or task for the same cases;
- a decision recorded against the exact fixture hash;
- a new fixture version if any expected value or classification changes.
