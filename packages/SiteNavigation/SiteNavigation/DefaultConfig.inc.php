<?php
$defaultConfig = array(
    'AuxConfig' => [
        'firstLevelDefaultValue' => 'home',
        'applyDefaultValueFromLevel' => 0,
        'actionName' => 'action',
        'validationRegExp' => '/^[a-zA-Z0-9_\-]+$/',
        'controllersDir' => 'controllers',
        'defaultControllerPath' => 'default'
    ],
    
    'Objects' => [
        'RequestParser' => 'requestParser',
        'Controller' => 'controller'
    ],
    'ObjectsIgnored' => [
        'Nav' => 'nav'
    ],
    'Hooks' => [
        'RequestParser' => 'Parse',
        'Controller' => 'ExecController'
    ]
);
