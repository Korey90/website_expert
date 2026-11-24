# Website Expert AI — pilot runbook

## Bezpieczeństwo

Moduł pozostaje na gałęzi feature/website-expert-ai-sprint-01. Bez autoryzacji właściciela nie uruchamiać na produkcji.

- Publiczny widget i endpoint nie istnieją.
- Panel AI wymaga access_admin_panel + view_ai_knowledge; edycja manage_ai_knowledge, publikacja publish_ai_knowledge, tester test_ai_knowledge.
- Admin ma uprawnienia AI; manager i developer nie mają ich domyślnie.
- Dla wszystkich zapytań używać serwerowo ustalonego business_id; brak domyślnego business przy publicznej sesji.
- Wiedza jest draft do czasu zatwierdzenia i pełnego indeksowania.
- Silnik RAG na pilocie zwraca wyłącznie dosłowne, weryfikowalne cytaty, bez generowania dodatkowych twierdzeń.
- AI Knowledge w Settings. Osobna kolejka ai.

## Pilot lokalny (dopiero po audycie i testach)

1. Sprawdzić composer.lock, konfigurację PHP/DB i testy regresyjne.
2. Uruchomić migracje na *lokalnej testowej* bazie danych.
3. W środowisku deweloperskim ustawić:
   - AI_ASSISTANT_PREVIEW_ENABLED=true
   - OPENAI_API_KEY=sekret (nigdy w repozytorium)
   - OPENAI_BASE_URL=https://api.openai.com/v1
   - AI_ASSISTANT_CHAT_MODEL i AI_ASSISTANT_EMBEDDING_MODEL (wspierane modele)
   - AI_ASSISTANT_EMBEDDING_DIMENSIONS=1536
   - AI_ASSISTANT_DAILY_CALLS=20
   - AI_ASSISTANT_DAILY_BUDGET_MICROUSD=100000
   - AI_ASSISTANT_RESERVATION_MICROUSD=2000
4. Uruchomić worker \`php artisan queue:work --queue=ai,default --tries=1\`.
5. W panelu AI Knowledge wybrać firmę, włączyć asystenta (tylko na dev), dodać dokument en/pl/pt jako szkic, zatwierdzić wersję, sprawdzić indeks i tester.
6. Monitorować ai_usage_logs, kolejkę i błędy. Wartości są *rezerwacjami*, nie rzeczywistą ceną z OpenAI.
7. Nie publikować na produkcji bez pełnej walidacji, testów tenant isolation i przeglądu bezpieczeństwa.

## Walidacja do wykonania w środowisku z zależnościami

\`\`\`bash
php -l app/Actions/AI/SaveKnowledgeDraftAction.php
php -l app/Actions/AI/IndexKnowledgeVersionAction.php
php artisan test --filter=AiKnowledgeWorkflowTest
php artisan test --filter=KnowledgeChunkerTest
php artisan test --filter=AiVectorTest
php artisan test --filter=FilamentPermissionAccessTest
php artisan test --filter=TenantIsolationTest
vendor/bin/pint --test
php artisan view:cache
\`\`\`

PHPUnit/Composer, faktyczne migracje i podgląd przeglądarkowy nie zostały wykonane z poziomu integracji GitHub API.
