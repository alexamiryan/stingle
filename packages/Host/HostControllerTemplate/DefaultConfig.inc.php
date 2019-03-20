<?php

$defaultConfig = array(
	'Hooks' => array(
		'AfterRequestParser' => 'SetTemplateByHost'
	),
	'Memcache' => array(
		'HostControllerTemplate' => -1
	)
);
