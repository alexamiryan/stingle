<?php
$defaultConfig = array(
    'AuxConfig' => array(
        'secondFactorAuthName' => 'yubiKey',
        'yubico_id' => '4264',
        'yubico_key' => 'ETbmajX8ozu1h/cqvRvBD28G6A4='),
    'Hooks' => array('OnUserLogin' => 'YubicoAuth'),
    'Tables' => [
        'wum_yubico_auth_groups' => 1,
        'wum_yubico_auth_users' => 1,
        'wum_yubico_keys' => 1,
        'wum_yubico_keys_to_groups' => 1,
        'wum_yubico_keys_to_users' => 1
    ]
);
