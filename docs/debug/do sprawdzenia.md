Read [](file:///d%3A/Works/web-dev-app/docs/poprawki.md#1-1), lines 1 to 80

Wszystkie 10 problemów zostało naprawionych w tej i poprzedniej sesji. Oto co zrobiłem i **co powinieneś przetestować**:

---

## Zrobione ✅

| # | Co | Plik |
|---|---|---|
| 1+3 | `ContractInterpolationService` — zastępuje `{{legal.*}}`, `{{client.*}}`, `{{project.*}}`, `{{contract.*}}` | ContractInterpolationService.php |
| 2 | Seeder szablonów zaktualizowany — `[NAZWA KLIENTA]` → `{{client.company_name}}` itd. (12 miejsc, 6 szablonów), ponownie zaseedowany | ContractTemplateSeeder.php |
| 4 | Przycisk "Create Contract" na widoku Leada (widoczny gdy `is_won`) | ViewLead.php |
| 5 | Triggery `contract.created/sent/signed/expired` w `AutomationRuleResource` | AutomationRuleResource.php |
| 6 | Bug Lead.status naprawiony — usunięto `wasChanged('status')` i `$lead->status` | AutomationEventListener.php |
| 7 | Migracja `contract_template_id` + relacja `belongsTo` na modelu | Contract.php |
| 8 | Przycisk "Create Contract" na widoku Quote (widoczny gdy `accepted`/`sent`) | ViewQuote.php |
| 9 | Form kontraktu prefilluje `value`/`currency` z URL params + z Project | ContractResource.php |

---

## Co testować

### 1. Interpolacja szablonu
- Przejdź do **Contracts → Create**
- Wybierz klienta → wybierz szablon → sprawdź czy `{{legal.company_name}}`, `{{client.company_name}}` itd. zostały zastąpione prawdziwymi danymi w polu `terms`

### 2. Prefill z Lead
- Otwórz Lead w etapie **Won** → powinien pojawić się przycisk **"Create Contract"**
- Kliknij → formularz kontraktu otworzy się z prefillowanymi `client_id`, `project_id`, `value`, `currency`

### 3. Prefill z Quote
- Otwórz Quote ze statusem **accepted** lub **sent** → przycisk **"Create Contract"**
- Kliknij → formularz z prefillowanym `client_id`, `quote_id`, `value`, `currency`

### 4. Prefill wartości z projektu
- W formularzu kontraktu wybierz projekt → pola `value` i `currency` powinny się automatycznie wypełnić budżetem projektu

### 5. Triggery automatyzacji kontraktu
- Przejdź do **Automations → Create Rule** → w polu `Trigger Event` powinny być dostępne: `Contract Created`, `Contract Sent`, `Contract Signed`, `Contract Expired`

### 6. Podgląd szablonu
- Przejdź do **Contract Templates → View** dowolnego szablonu → Copy/Print powinny działać, `{{legal.*}}` powinny być zastąpione w podglądzie