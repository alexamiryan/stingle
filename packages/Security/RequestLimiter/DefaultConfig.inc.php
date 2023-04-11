<?php

$defaultConfig = [
	'AuxConfig' => [
		'limits' => [
			'gen' => 100
		],
        'defaultReleaseTime' => 20 //minutes
	],
	'Objects' => [
		'RequestLimiter' => 'requestLimiter'
	],
	'Hooks' => [
		'BeforeController' => 'RequestLimiterGeneralRun',
		'RecordRequest' => 'RecordRequest'
	],
    'Tables' => [
        'security_flooder_ips' => 2,
        'security_requests_log' => 1
    ]
];
