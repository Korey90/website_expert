<?php

return [

    // ── Etiquetas de status do pedido ─────────────────────────────────────────
    'status' => [
        'pending_payment' => 'Aguardando Pagamento',
        'paid'            => 'Pago',
        'registering'     => 'A Registar',
        'completed'       => 'Concluído',
        'failed'          => 'Falhou',
        'cancelled'       => 'Cancelado',
    ],

    // ── Etiquetas de tipo de ação ─────────────────────────────────────────────
    'action' => [
        'register' => 'Registo',
        'renew'    => 'Renovação',
        'transfer' => 'Transferência',
    ],

    // ── Lembrete de renovação ─────────────────────────────────────────────────
    'reminder' => [
        'subject'   => 'O seu domínio :domain expira em :days dia(s)',
        'days_30'   => 'O seu domínio :domain irá expirar em 30 dias. Por favor, renove-o para evitar interrupções.',
        'days_14'   => 'O seu domínio :domain irá expirar em 14 dias. Renove agora para manter o domínio ativo.',
        'days_7'    => 'O seu domínio :domain irá expirar em 7 dias. Urgente — renove imediatamente.',
        'days_1'    => 'O seu domínio :domain expira amanhã! Renove agora para evitar que fique offline.',
        'expired'   => 'O seu domínio :domain expirou. Contacte-nos para discutir opções de reativação.',
    ],

    // ── Etiquetas de interface ────────────────────────────────────────────────
    'label' => [
        'domain_name'    => 'Nome do domínio',
        'tld'            => 'Extensão (TLD)',
        'expires_at'     => 'Expira em',
        'registered_at'  => 'Registado em',
        'years'          => ':count ano(s)',
        'auto_renew'     => 'Renovação automática',
        'whois_privacy'  => 'Privacidade WHOIS',
        'nameservers'    => 'Servidores de nomes',
        'register_price' => 'Preço de registo',
        'renew_price'    => 'Preço de renovação',
        'transfer_price' => 'Preço de transferência',
        'available'      => 'Disponível',
        'taken'          => 'Indisponível',
        'checking'       => 'A verificar disponibilidade…',
    ],

    // ── Mensagens de erro ─────────────────────────────────────────────────────
    'error' => [
        'unavailable'           => 'O domínio :domain não está disponível para registo.',
        'registration_failed'   => 'O registo do domínio :domain falhou. A nossa equipa foi notificada.',
        'renewal_failed'        => 'A renovação do domínio :domain falhou. Por favor contacte o suporte.',
        'transfer_failed'       => 'A transferência do domínio :domain falhou. Verifique o código de autorização e tente novamente.',
        'nameservers_failed'    => 'Falha ao atualizar os servidores de nomes para :domain.',
        'order_not_cancellable' => 'Este pedido não pode ser cancelado no estado atual.',
        'stripe_not_configured' => 'Os pagamentos online não estão configurados. Por favor contacte o suporte.',
    ],

    // ── Notificações ──────────────────────────────────────────────────────────
    'notification' => [
        'order_placed_subject'   => 'Pedido de domínio confirmado — :domain',
        'order_placed_body'      => 'Obrigado pelo seu pedido. Estamos a processar o seu domínio :domain (:action).',
        'registered_subject'     => 'O seu domínio :domain foi registado',
        'registered_body'        => 'Ótimas notícias! O seu domínio :domain foi registado com sucesso e está agora ativo.',
        'failed_subject'         => 'Registo do domínio falhou — :domain',
        'failed_body'            => 'Infelizmente não foi possível registar :domain. A nossa equipa foi notificada e entrará em contacto em breve.',
    ],

];
