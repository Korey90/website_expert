<?php

return [
    'enabled' => (bool) env('AI_ASSISTANT_ENABLED', false),
    // Server-owned agency tenant; never accept this identifier from a public request.
    'public_business_id' => env('AI_ASSISTANT_PUBLIC_BUSINESS_ID'),
    'max_messages_per_conversation' => 40,
    'conversation_retention_days' => (int) env('AI_ASSISTANT_RETENTION_DAYS', 30),
    'retention_enabled' => (bool) env('AI_ASSISTANT_RETENTION_ENABLED', false),
    'max_history_messages' => 30,
    'preview_enabled' => (bool) env('AI_ASSISTANT_PREVIEW_ENABLED', false),
    'chat_model' => env('AI_ASSISTANT_CHAT_MODEL', 'gpt-4o-mini'),
    'embedding_model' => env('AI_ASSISTANT_EMBEDDING_MODEL', 'text-embedding-3-small'),
    'dimensions' => (int) env('AI_ASSISTANT_EMBEDDING_DIMENSIONS', 1536),
    'daily_calls' => (int) env('AI_ASSISTANT_DAILY_CALLS', 0),
    'daily_budget_microusd' => (int) env('AI_ASSISTANT_DAILY_BUDGET_MICROUSD', 0),
    // Operator must set a conservative upper bound per API request, not a market price.
    'reservation_microusd' => (int) env('AI_ASSISTANT_RESERVATION_MICROUSD', 0),
    'max_document_chars' => 30000,
    'max_question_chars' => 2000,
    'max_chunks' => 1000,
    'chunk_chars' => 1800,
    'overlap_chars' => 250,
    'top_k' => 5,
    // Pilot threshold only; requires a live evaluation before public use.
    'similarity_threshold' => 0.55,
    'max_output_tokens' => 500,
];
