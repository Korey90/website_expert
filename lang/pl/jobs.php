<?php

return [
    'tab_failed'           => 'Nieudane joby',
    'tab_pending'          => 'Oczekujące joby',
    'tab_batches'          => 'Batche jobów',

    'queue'                => 'Kolejka',
    'all_queues'           => 'Wszystkie kolejki',
    'search'               => 'Szukaj',
    'search_placeholder'   => 'Szukaj po klasie, UUID, wyjątku…',
    'job_class'            => 'Klasa joba',
    'failed_at'            => 'Data błędu',
    'created_at'           => 'Data dodania',
    'attempts'             => 'Próby',
    'status'               => 'Status',
    'actions'              => 'Akcje',

    'batch_name'           => 'Nazwa batcha',
    'progress'             => 'Postęp',
    'total'                => 'Łącznie',
    'pending'              => 'Oczekuje',
    'failed_count'         => 'Błędy',

    'running'              => 'W trakcie',
    'waiting'              => 'Oczekuje',
    'cancelled'            => 'Anulowany',
    'finished'             => 'Zakończony',

    'retry'                => 'Ponów',
    'retry_all'            => 'Ponów wszystkie',
    'flush_all'            => 'Usuń wszystkie',
    'delete'               => 'Usuń',
    'cancel'               => 'Anuluj',
    'view_payload'         => 'Podgląd payloadu',
    'view_exception'       => 'Podgląd wyjątku',
    'exception'            => 'Wyjątek',

    'page'                 => 'Strona',
    'records'              => 'rekordów',
    'prev'                 => 'Poprzednia',
    'next'                 => 'Następna',

    'no_failed'            => 'Brak nieudanych jobów.',
    'no_pending'           => 'Brak jobów oczekujących w kolejce.',
    'no_batches'           => 'Brak batchów jobów.',

    'retry_success'        => 'Job został dodany do ponownego przetworzenia.',
    'retry_all_success'    => 'Wszystkie nieudane joby zostały ponownie kolejkowane.',
    'delete_success'       => 'Job usunięty.',
    'flush_success'        => 'Wszystkie nieudane joby zostały usunięte.',
    'batch_cancelled'      => 'Batch anulowany.',
    'batch_not_found'      => 'Batch nie został znaleziony.',

    'confirm_retry'        => 'Ponowić ten job?',
    'confirm_retry_all'    => 'Ponowić wszystkie nieudane joby? Wszystkie trafią ponownie do kolejki.',
    'confirm_delete'       => 'Usunąć ten wpis? Tej operacji nie można cofnąć.',
    'confirm_cancel_batch' => 'Anulować ten batch? Uruchomione joby dokończą działanie, ale nowe nie będą wysyłane.',

    'flush_confirm_title'  => 'Usuń wszystkie nieudane joby',
    'flush_confirm_body'   => 'Spowoduje to trwałe usunięcie wszystkich wpisów z tabeli nieudanych jobów. Tej operacji nie można cofnąć.',

    'restart_workers'      => 'Restartuj workery',
    'restart_success'      => 'Sygnał restartu wysłany.',
    'restart_body'         => 'Działające workery zakończą bieżące joby i zrestartują się.',
    'restart_confirm_body' => 'Wysyła sygnał queue:restart. Działające workery dokończą bieżący job i zrestartują się. Nie przerywa pracy w toku.',

    'worker'               => 'Worker',
    'worker_running'       => 'Aktywny',
    'worker_idle'          => 'Nieaktywny',
    'queues_breakdown'     => 'Podział według kolejek',
];
