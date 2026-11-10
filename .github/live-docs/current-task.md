# Current Task

**Status:** W toku — część techniczna; formalna akceptacja wymaga księgowego UK
**Zadanie:** UKFIN-511 — Golden dataset i review gate księgowego
**Data rozpoczęcia:** 2026-11-10
**Plan nadrzędny:** `docs/plans/uk-finance-tax-module-plan.md`
**Ochrona równoległej pracy:** nie modyfikować `resources/js/Pages/Landing/PlStronyInternetowe.tsx` ani `package*.json`.

## Cel

Utworzyć deterministyczny, wersjonowany zestaw referencyjny dla fazy raportów i VAT workspace, który odtwarza reprezentatywne operacje UK, generuje oczekiwane wyniki ledger/raportów/VAT Return/accountant pack oraz udostępnia księgowemu jednoznaczną checklistę akceptacji. Zadanie można technicznie przygotować i zautomatyzować, ale nie wolno oznaczyć wyniku jako zaakceptowanego bez rzeczywistego podpisu lub udokumentowanej decyzji księgowego UK.

## Zakres techniczny

- Wersjonowany fixture źródłowy z jawną wersją schematu, założeniami i fikcyjnymi kontrahentami.
- Przypadki: standard/reduced/zero/exempt/outside scope, zakupy z odliczalnym i nieodliczalnym VAT, reverse charge, sales/purchase credit, payment/refund, FX, VAT adjustment i reversal.
- Oczekiwane journale, Trial Balance, P&L, Balance Sheet, management Cash Flow, ageing, VAT Detail oraz wszystkie 9 pól VAT Return.
- Automatyczny test zgodności co do pensa oraz stabilności snapshotu/manifestu.
- Pakiet review dla księgowego oparty na rzeczywistym generatorze UKFIN-510, nie na równoległej demonstracyjnej implementacji.
- Checklista decyzji dla mapowań tax codes, boxes 6/7/8/9, NI–EU, reverse charge, non-recoverable VAT i error corrections.
- Plik sign-off z miejscem na imię, kwalifikacje/firmę, datę, wersję datasetu, decyzję i uwagi; domyślny status `pending`.

## Granice i gate

- Dane transakcyjne pozostają fikcyjne; dane wystawcy to `Website Expert NI` zgodnie z ustawieniami systemu.
- Dataset nie jest poradą podatkową i nie jest deklaracją wysłaną do HMRC.
- Testy mogą potwierdzić zgodność kodu z zaakceptowanym fixturem, ale nie mogą same zatwierdzić poprawności podatkowej fixtureu.
- UKFIN-511 pozostaje w toku do czasu ręcznej akceptacji przez prawdziwego księgowego UK; część techniczna otrzyma osobny status „ready for accountant review”.

## Plan walidacji

- Determinizm: dwie generacje tych samych danych dają identyczne wartości i snapshot hash.
- Uzgodnienia: double-entry, VAT control accounts, AR/AP, cash i wszystkie raportowe sumy kontrolne są równe co do pensa.
- Expected-vs-actual dla każdego journalu, raportu i dziewięciu pól.
- Accountant pack: integralność ZIP/manifestu oraz brak pełnych danych wrażliwych.
- Dokumentacja księgowego zawiera komplet założeń i otwartych decyzji.
- Pint, test dedykowany, Finance regression i pełny baseline po zmianach produkcyjnych.

## Stan realizacji

- ✅ Fixture `v1.json` księguje reprezentatywne operacje przez produkcyjny posting engine.
- ✅ Expected-vs-actual obejmuje Trial Balance, P&L, Balance Sheet, management Cash Flow, ageing i wszystkie 9 pól VAT Return.
- ✅ Dwie budowy snapshotu dają identyczne dane i SHA-256.
- ✅ Locked snapshot generuje rzeczywisty UKFIN-510 accountant pack z redakcją numeru VAT.
- ✅ Fixture jest chroniony stałym SHA-256; zmiana in-place łamie test i wymaga nowej wersji.
- ✅ README, szczegółowa checklista i formularz sign-off są gotowe w `docs/review/uk-finance-golden-dataset-v1/`.
- ✅ Regresja modułu Finance: 269 passed / 1 skipped / 2008 assertions.
- ⏳ Formalna akceptacja podatkowa przez prawdziwego księgowego UK pozostaje oczekującym zewnętrznym gate.
