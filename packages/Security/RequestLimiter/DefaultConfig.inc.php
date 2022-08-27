<?php

$defaultConfig = [
	'AuxConfig' => [
		'limits' => [
			'gen' => 100
		]
	],
	'Objects' => [
		'RequestLimiter' => 'requestLimiter'
	],
	'Hooks' => [
		'BeforeController' => 'RequestLimiterGeneralRun',
		'RecordRequest' => 'RecordRequest'
	],
    'Tables' => [
        'security_flooder_ips' => 1,
        'security_requests_log' => 1
    ]
];
