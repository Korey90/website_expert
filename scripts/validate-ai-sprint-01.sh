#!/usr/bin/env bash
set -Eeuo pipefail

if [[ -f .env ]] && grep -Eq '^APP_ENV=(production|prod)([[:space:]]*|$)' .env; then
    echo 'Refusing to run validation from a production .env.' >&2
    exit 1
fi

if [[ ! -f vendor/autoload.php ]]; then
    echo 'Missing vendor/autoload.php. Install Composer dependencies in a safe local checkout.' >&2
    exit 1
fi

export APP_ENV=testing
export APP_DEBUG=false
export DB_CONNECTION=sqlite
export DB_DATABASE=':memory:'
export SESSION_DRIVER=array
export CACHE_STORE=array
export QUEUE_CONNECTION=sync
export BROADCAST_CONNECTION=null
export MAIL_MAILER=array
export OPENAI_API_KEY=''
export AI_ASSISTANT_PREVIEW_ENABLED=false

echo 'Checking PHP syntax...'
find app/Actions/AI app/Services/AI tests/Feature/AI tests/Unit/AI -type f -name '*.php' -print0 | xargs -0 -n 1 php -l
php -l database/migrations/2026_11_24_200001_create_ai_knowledge_tables.php
php -l app/Jobs/IndexAiKnowledgeVersionJob.php
php -l app/Filament/Pages/AiKnowledgePage.php

echo 'Running AI-specific PHPUnit tests...'
php artisan test tests/Unit/AI tests/Feature/AI --stop-on-failure

echo 'Running multi-tenant and Filament access regressions...'
php artisan test tests/Feature/TenantIsolationTest.php tests/Feature/Filament/FilamentPermissionAccessTest.php --stop-on-failure

echo 'Checking Blade view compilation...'
php artisan view:cache

echo 'Checking Pint formatting without modifying files...'
vendor/bin/pint --test app/Actions/AI app/Services/AI app/Jobs/IndexAiKnowledgeVersionJob.php app/Filament/Pages/AiKnowledgePage.php tests/Feature/AI tests/Unit/AI

echo 'Running full Laravel regression suite...'
php artisan test

echo 'Sprint 01: testing suite completed.'
