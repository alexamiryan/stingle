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
	]
];
