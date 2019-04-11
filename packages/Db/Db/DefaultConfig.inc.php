<?php

$defaultConfig = [
	'AuxConfig' => [
		'hosts' => [
			'default' => [
				[
					'type' => 'rw',
					'host' => ':/var/lib/mysql/mysql.sock',
					'user' => 'root',
					'password' => '',
					'name' => '',
					'isPersistent' => false,
					'encoding' => 'UTF8'
				],
				[
					'type' => 'ro',
					'host' => ':/var/lib/mysql/mysql.sock',
					'user' => 'root',
					'password' => '',
					'name' => '',
					'isPersistent' => false,
					'encoding' => 'UTF8'
				]
			]
		]
	],
	'Objects' => [
		'Query' => 'sql'
	],
	'Hooks' => [
		'AfterPackagesLoad' => 'StoreTblCache'
	]
];
