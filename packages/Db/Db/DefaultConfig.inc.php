<?php

$defaultConfig = array(
	'AuxConfig' => array(
		'host' => ':/var/lib/mysql/mysql.sock',
		'user' => 'root',
		'password' => '',
		'name' => '',
		'isPersistent' => true,
		'encoding' => 'UTF8'
	),
	'Objects' => array(
		'Db' => 'db',
		'Query' => 'sql'
	),
	'Hooks' => array(
		'AfterPackagesLoad' => 'StoreTblCache'
	)
);
