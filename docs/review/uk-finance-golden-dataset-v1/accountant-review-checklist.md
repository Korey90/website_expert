# UK Finance golden dataset v1 — accountant checklist

Dataset ID: `website-expert-ni-uk-finance-golden-v1`  
Expected SHA-256: `6271a6a9e179c1a8769e7fd957f401fde892b0421ea2a73bdc7a0918fd2672a1`

Reviewer: ______________________________  
Firm / professional body: ______________________________  
Date: ______________________________

## Dataset integrity and boundary

- [ ] The reviewed `v1.json` SHA-256 matches the expected hash.
- [ ] The reviewer understands that all transactions are fictional.
- [ ] The scope is standard VAT accounting on an accrual basis in GBP.
- [ ] The output is an internal working paper and not an HMRC submission.

## Tax-code treatments

- [ ] `S20` sales correctly affect output VAT Box 1 and net sales Box 6.
- [ ] `S20` purchases correctly affect input VAT Box 4 and net purchases Box 7.
- [ ] `R5` sales and purchases use the same box mapping at the reduced rate.
- [ ] `Z0` values enter Boxes 6/7 without VAT in Boxes 1/4.
- [ ] Exempt values enter Boxes 6/7 without VAT in Boxes 1/4.
- [ ] Outside-scope values are correctly excluded from Boxes 6/7 for the represented facts.
- [ ] The reverse-charge service purchase correctly creates equal Box 1 and Box 4 VAT and enters Box 7.
- [ ] The capital purchase correctly enters Box 4/7 and the Balance Sheet rather than P&L operating expenses.

## Credits, reversals and adjustments

- [ ] Customer credit reduces output VAT and Box 6 in the correct VAT period.
- [ ] Supplier credit reduces input VAT and Box 7 in the correct VAT period.
- [ ] A complete reversal remains auditable and nets the original VAT and ledger effects to zero.
- [ ] The £5 output VAT accounting adjustment has sufficient reason/audit metadata and no artificial Box 6 base.
- [ ] Error corrections requiring separate notification would remain outside the current VAT Return.

## Foreign currency

- [ ] The EUR sale may use the stored GBP rate snapshot for UK VAT and ledger reporting.
- [ ] The displayed source amount, exchange rate, GBP net and GBP VAT provide sufficient audit evidence.
- [ ] Any required HMRC exchange-rate source wording or policy is identified below.

## VAT Return and reports

- [ ] Boxes 1–5 retain penny precision and Boxes 6–9 use acceptable whole-pound submission values.
- [ ] Box 3 equals Box 1 + Box 2.
- [ ] Box 5 and payable/repayable direction are presented correctly.
- [ ] VAT Detail reconciles to VAT control accounts and provides adequate source traceability.
- [ ] Trial Balance, P&L and Balance Sheet classifications are acceptable.
- [ ] Management Cash Flow is clearly identified as a management report, not a statutory IAS 7 statement.
- [ ] Ageing treatment for manual ledger adjustments is acceptable or required changes are documented.
- [ ] Accountant ZIP contents and SHA manifest are sufficient for review evidence.

## Explicit open decisions

- [ ] Confirm whether outside-scope supplies/purchases should be included in Box 6/7 for any specific transaction classes used by Website Expert NI.
- [ ] Define and approve NI–EU goods scenarios for Boxes 8/9, or explicitly confirm they are not currently applicable.
- [ ] Define non-recoverable/partially recoverable input VAT scenarios and partial-exemption handling before use.
- [ ] Confirm domestic reverse-charge cases relevant to the business separately from the service reverse-charge example.
- [ ] Confirm evidence and approval requirements for VAT adjustments and prior-return error corrections.

## Required changes and comments

1. ____________________________________________________________________________
2. ____________________________________________________________________________
3. ____________________________________________________________________________
4. ____________________________________________________________________________
5. ____________________________________________________________________________

Recommended outcome: [ ] Accept v1  [ ] Accept with conditions  [ ] Rework required
