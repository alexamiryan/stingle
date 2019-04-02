<?php

$defaultConfig = array(
	'AuxConfig' => array(
		'enabled' => false,
		'host' => '127.0.0.1',
		'port' => "11211",
		'keyPrefix' => "",
		'poolSize' => 10
	),
	'Objects' => array(
		'Memcache' => 'memcache',
		'Query' => 'sql'
	),
	'Hooks' => array(
		'BeforePluginInit' => 'AddMemcacheTimeConfig'
	)
);
