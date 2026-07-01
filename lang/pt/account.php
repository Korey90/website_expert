<?php

return [
    // Sections
    'section_profile'       => 'Informações Pessoais',
    'section_profile_desc'  => 'Atualize seu nome, endereço de e-mail e outros dados do perfil.',
    'section_password'      => 'Alterar Senha',
    'section_password_desc' => 'Certifique-se de que sua conta usa uma senha forte e aleatória.',
    'section_2fa'           => 'Autenticação de Dois Fatores (2FA)',
    'section_2fa_desc'      => 'Adicione uma camada extra de segurança usando um aplicativo TOTP (ex.: Google Authenticator, Authy).',

    // Fields
    'name'             => 'Nome Completo',
    'email'            => 'Endereço de E-mail',
    'phone'            => 'Telefone',
    'locale'           => 'Idioma da Interface',
    'avatar'           => 'Foto de Perfil',
    'current_password' => 'Senha Atual',
    'new_password'     => 'Nova Senha',
    'confirm_password' => 'Confirmar Nova Senha',
    'totp_code'        => 'Código TOTP (6 dígitos)',
    'totp_code_disable'=> 'Código TOTP para confirmar desativação',

    // Buttons
    'save_profile'    => 'Salvar Perfil',
    'change_password' => 'Alterar Senha',
    'cancel'          => 'Cancelar',

    // 2FA
    '2fa_active'           => '2FA Ativo',
    '2fa_inactive'         => '2FA Inativo',
    '2fa_enable'           => 'Ativar 2FA',
    '2fa_disable'          => 'Desativar 2FA',
    '2fa_confirm'          => 'Confirmar e Ativar',
    '2fa_scan_instruction' => 'Escaneie o QR Code abaixo com seu aplicativo TOTP e insira o código de 6 dígitos gerado.',
    '2fa_manual_key'       => 'Chave de entrada manual',
    '2fa_enabled'          => 'O 2FA foi ativado.',
    '2fa_disabled'         => 'O 2FA foi desativado.',
    '2fa_code_invalid'     => 'Código TOTP inválido. Verifique o relógio do dispositivo e tente novamente.',
    '2fa_secret_missing'   => 'Segredo 2FA ausente. Reinicie o processo.',

    // Notifications
    'profile_saved'       => 'Perfil salvo com sucesso.',
    'password_changed'    => 'Senha alterada com sucesso.',
    'password_incorrect'  => 'A senha atual está incorreta.',
];
