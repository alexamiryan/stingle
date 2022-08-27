<?php
$defaultConfig = array(
    'AuxConfig' => array(
        'secondFactorAuthName' => 'googleAuth',
        'siteName' => 'GoogleAuth'),
    'Hooks' => array('OnUserLogin' => 'GoogleAuth'),
    'Tables' => [
        'wum_google_auth_map' => 1
    ]
);
