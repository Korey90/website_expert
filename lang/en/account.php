<?php

return [
    // Sections
    'section_profile'       => 'Personal Information',
    'section_profile_desc'  => 'Update your name, email address and other profile details.',
    'section_password'      => 'Change Password',
    'section_password_desc' => 'Make sure your account uses a strong, random password.',
    'section_2fa'           => 'Two-Factor Authentication (2FA)',
    'section_2fa_desc'      => 'Add an extra layer of security to your account using a TOTP app (e.g. Google Authenticator, Authy).',

    // Fields
    'name'             => 'Full Name',
    'email'            => 'Email Address',
    'phone'            => 'Phone',
    'locale'           => 'Interface Language',
    'avatar'           => 'Profile Picture',
    'current_password' => 'Current Password',
    'new_password'     => 'New Password',
    'confirm_password' => 'Confirm New Password',
    'totp_code'        => 'TOTP Code (6 digits)',
    'totp_code_disable'=> 'TOTP Code to confirm disabling',

    // Buttons
    'save_profile'    => 'Save Profile',
    'change_password' => 'Change Password',
    'cancel'          => 'Cancel',

    // 2FA
    '2fa_active'           => '2FA Enabled',
    '2fa_inactive'         => '2FA Disabled',
    '2fa_enable'           => 'Enable 2FA',
    '2fa_disable'          => 'Disable 2FA',
    '2fa_confirm'          => 'Confirm & Activate',
    '2fa_scan_instruction' => 'Scan the QR code below with your TOTP app, then enter the 6-digit code it generates.',
    '2fa_manual_key'       => 'Manual entry key',
    '2fa_enabled'          => '2FA has been enabled.',
    '2fa_disabled'         => '2FA has been disabled.',
    '2fa_code_invalid'     => 'Invalid TOTP code. Check your device clock and try again.',
    '2fa_secret_missing'   => '2FA secret is missing. Please start the process again.',

    // Notifications
    'profile_saved'       => 'Profile saved.',
    'password_changed'    => 'Password changed successfully.',
    'password_incorrect'  => 'The current password is incorrect.',
];
