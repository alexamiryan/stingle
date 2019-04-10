<?php

$defaultConfig = [
	'AuxConfig' => [
		'hosts' => [
			'default' => [
				'host' => ':/var/lib/mysql/mysql.sock',
				'user' => 'root',
				'password' => '',
				'name' => '',
				'isPersistent' => true,
				'encoding' => 'UTF8'
			]
		]
	],
	'Objects' => [
		'Db' => 'db',
		'Query' => 'sql'
	],
	'Hooks' => [
		'AfterPackagesLoad' => 'StoreTblCache'
	]
];
