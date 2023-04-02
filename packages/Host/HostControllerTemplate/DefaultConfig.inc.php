<?php

$defaultConfig = [
	'Hooks' => [
		'AfterRequestParser' => 'SetTemplateByHost'
	],
	'Memcache' => [
		'HostControllerTemplate' => -1
	],
    'Tables' => [
        'host_controller_template' => 1
    ]
];
