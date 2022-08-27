<?php

$defaultConfig = [
	'AuxConfig' => [
	
	],
	'Hooks' => [
		'BeforePluginLoadObjects' => 'RunMigrations'
	],
    'Tables' => [
        'db_migrations' => 1
    ],
];
