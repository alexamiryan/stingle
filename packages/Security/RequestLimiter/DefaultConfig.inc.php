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
	'Memcache' => [
		'RequestLimiter' => -1
	],
	'Hooks' => [
		'BeforeController' => 'RequestLimiterGeneralRun',
		'RecordRequest' => 'RecordRequest'
	]
];
