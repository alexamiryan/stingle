<?php

$defaultConfig = [
	'AuxConfig' => [
		'instances' => [
			'default' => [
				/*'endpoints' => [
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
				],
				'readsFromRWEndpoint' => false*/
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
