<?php

return [
    'tab_failed'           => 'Jobs falhos',
    'tab_pending'          => 'Jobs pendentes',
    'tab_batches'          => 'Lotes de jobs',

    'queue'                => 'Fila',
    'all_queues'           => 'Todas as filas',
    'search'               => 'Pesquisar',
    'search_placeholder'   => 'Pesquisar por classe, UUID, exceção…',
    'job_class'            => 'Classe do job',
    'failed_at'            => 'Falhou em',
    'created_at'           => 'Criado em',
    'attempts'             => 'Tentativas',
    'status'               => 'Estado',
    'actions'              => 'Ações',

    'batch_name'           => 'Nome do lote',
    'progress'             => 'Progresso',
    'total'                => 'Total',
    'pending'              => 'Pendente',
    'failed_count'         => 'Falhas',

    'running'              => 'A executar',
    'waiting'              => 'Aguarda',
    'cancelled'            => 'Cancelado',
    'finished'             => 'Concluído',

    'retry'                => 'Repetir',
    'retry_all'            => 'Repetir todos',
    'flush_all'            => 'Eliminar todos',
    'delete'               => 'Eliminar',
    'cancel'               => 'Cancelar',
    'view_payload'         => 'Ver payload',
    'view_exception'       => 'Ver exceção',
    'exception'            => 'Exceção',

    'page'                 => 'Página',
    'records'              => 'registos',
    'prev'                 => 'Anterior',
    'next'                 => 'Próxima',

    'no_failed'            => 'Nenhum job falhado.',
    'no_pending'           => 'Nenhum job pendente na fila.',
    'no_batches'           => 'Nenhum lote de jobs encontrado.',

    'retry_success'        => 'Job adicionado para nova tentativa.',
    'retry_all_success'    => 'Todos os jobs falhados foram recolocados na fila.',
    'delete_success'       => 'Job eliminado.',
    'flush_success'        => 'Todos os jobs falhados foram eliminados.',
    'batch_cancelled'      => 'Lote cancelado.',
    'batch_not_found'      => 'Lote não encontrado.',

    'confirm_retry'        => 'Repetir este job?',
    'confirm_retry_all'    => 'Repetir todos os jobs falhados? Todos serão recolocados na fila.',
    'confirm_delete'       => 'Eliminar este registo? Esta ação não pode ser revertida.',
    'confirm_cancel_batch' => 'Cancelar este lote? Os jobs em execução serão concluídos, mas não serão enviados novos.',

    'flush_confirm_title'  => 'Eliminar todos os jobs falhados',
    'flush_confirm_body'   => 'Isto irá eliminar permanentemente todas as entradas da tabela de jobs falhados. Esta ação não pode ser revertida.',

    'restart_workers'      => 'Reiniciar workers',
    'restart_success'      => 'Sinal de reinício enviado.',
    'restart_body'         => 'Os workers em execução concluirão o job atual e reiniciarão.',
    'restart_confirm_body' => 'Envia um sinal queue:restart. Os workers terminarão o job atual antes de reiniciar. Nenhum trabalho em curso será perdido.',

    'worker'               => 'Worker',
    'worker_running'       => 'Ativo',
    'worker_idle'          => 'Inativo',
    'queues_breakdown'     => 'Distribuição por fila',
];
