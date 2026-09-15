<?php

return [

    'meta' => [
        'title' => 'Verificador Gratuito de Posição no Google — Website Expert',
        'description' => 'Verifique em que posição o seu site aparece no Google para qualquer palavra-chave e localização. Grátis, instantâneo, sem registo.',
    ],

    'h1' => 'Verificador Gratuito de Posição no Google',
    'intro' => 'Verifique em que posição o seu site aparece no Google para qualquer palavra-chave e localização.',

    'form' => [
        'keyword_label' => 'Palavra-chave',
        'keyword_placeholder' => 'ex.: web design belfast',
        'website_label' => 'Website',
        'website_placeholder' => 'ex.: suaempresa.co.uk',
        'location_label' => 'Localização',
        'location_custom_option' => 'Outra localização…',
        'location_custom_placeholder' => 'ex.: Manchester, United Kingdom',
        'device_label' => 'Dispositivo',
        'device_desktop' => 'Computador',
        'device_mobile' => 'Telemóvel',
        'submit_label' => 'Verificar Posição',
        'loading_label' => 'A verificar resultados do Google…',
    ],

    'result' => [
        'position_label' => 'Posição',
        'ranking_page_label' => 'Página encontrada',
        'checked_for' => 'Resultados para ":keyword" — :location, :device',
        'first_page_message' => 'O seu site aparece na primeira página do Google para esta palavra-chave.',
        'interpretation' => [
            'top3' => 'Excelente visibilidade',
            'top10' => 'Primeira página do Google',
            'top20' => 'Perto da primeira página',
            'top50' => 'O seu site está posicionado, mas com visibilidade limitada',
            'top100' => 'O seu site está posicionado fora dos resultados principais visíveis',
        ],
        'not_found_title' => 'Não encontrado nos 100 primeiros resultados',
        'not_found_message' => 'Não foi possível encontrar :domain nos 100 primeiros resultados orgânicos do Google para ":keyword" na localização selecionada.',
        'error_title' => 'Não foi possível concluir a verificação',
        'error_message' => 'Não foi possível concluir a verificação agora. Por favor, tente novamente em breve.',
        'disclaimer' => 'Os resultados do Google variam consoante a localização, dispositivo, idioma e histórico de pesquisa. Este resultado reflete a localização e o dispositivo selecionados, não uma posição garantida para todos os utilizadores.',
    ],

    'cta' => [
        'good_title' => 'O seu site já está bem posicionado. Descubra o que pode ajudá-lo a subir ainda mais.',
        'good_button' => 'Pedir uma análise SEO',
        'bad_title' => 'Quer melhorar a sua visibilidade no Google?',
        'bad_body' => 'Peça uma auditoria SEO gratuita e descubra o que está a limitar o seu site.',
        'bad_button' => 'Obter auditoria SEO gratuita',
        'contact_button' => 'Contacte-nos',
    ],

    'faq' => [
        ['q' => 'Este verificador de posição no Google é gratuito?', 'a' => 'Sim. Pode verificar a sua posição no Google para qualquer palavra-chave, domínio, localização e dispositivo gratuitamente, sem necessidade de registo.'],
        ['q' => 'Por que a minha posição é diferente da que vejo no Google?', 'a' => 'Os resultados do Google dependem da localização, dispositivo, idioma, histórico de pesquisa e centro de dados a que está ligado. Esta ferramenta mostra um resultado representativo para a localização e dispositivo selecionados, não uma posição garantida para todos os utilizadores.'],
        ['q' => 'Quantos resultados a ferramenta pesquisa?', 'a' => 'A ferramenta pesquisa os 100 primeiros resultados orgânicos do Google para a sua palavra-chave e localização.'],
        ['q' => 'E se o meu site não for encontrado?', 'a' => 'Se o seu site não aparecer nos 100 primeiros resultados orgânicos, iremos informá-lo — é um resultado válido, não um erro.'],
        ['q' => 'Isto verifica subdomínios?', 'a' => 'Se introduzir um domínio principal (ex.: example.com), os subdomínios correspondentes (ex.: shop.example.com) são incluídos. Se introduzir um subdomínio específico, apenas esse é verificado.'],
        ['q' => 'Com que frequência posso verificar uma palavra-chave?', 'a' => 'Para manter a ferramenta gratuita para todos, as verificações têm um limite e verificações idênticas ficam em cache durante algumas horas.'],
    ],

    'errors' => [
        'keyword_required' => 'Introduza uma palavra-chave para verificar.',
        'website_required' => 'Introduza o endereço do seu website.',
        'location_required' => 'Selecione uma localização de pesquisa.',
        'invalid_domain' => 'Introduza um endereço de website válido (ex.: example.co.uk).',
        'recaptcha_failed' => 'Não foi possível verificar o seu pedido. Tente novamente.',
        'rate_limited' => 'Atingiu o limite de verificações. Tente novamente mais tarde.',
    ],

    'contact' => [
        'ctaFormTitle' => 'Fale-nos sobre as suas necessidades',
        'labelName' => 'Nome completo',
        'labelContact' => 'Email ou número de telefone',
        'labelMessage' => 'Mensagem',
        'labelWebsite' => 'Website',
        'placeholderName' => 'João Silva',
        'placeholderContact' => 'email ou telefone',
        'placeholderWebsite' => 'suaempresa.co.uk',
        'gdprText' => 'Concordo com o processamento dos meus dados pessoais de acordo com a',
        'gdprLink' => 'política de privacidade',
        'submitBtn' => 'Enviar pedido',
        'sending' => 'A enviar…',
        'successTitle' => 'Entraremos em contacto!',
        'successMsg' => 'Obrigado pelo contacto. Responderemos em 24 horas úteis.',
        'errorMsg' => 'Ocorreu um erro. Verifique os seus dados e tente novamente.',
        'validationMsg' => 'Preencha o nome, contacto e mensagem.',
        'clearMessage' => 'Limpar mensagem',
    ],

];
