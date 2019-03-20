<?php

$defaultConfig = array(
	'AuxConfig' => array(
		'memcacheEnabled' => true,
		'throwExceptionOnNotFound' => false,
		'defineAllConstsOn' => false
	),
	'Objects' => array(
		'LanguageManager' => 'lm'
	),
	'ObjectsIgnored' => array(
		'Language' => 'language'
	),
	'Hooks' => array(
		'AfterThisPluginTreeInit' => 'GetLanguageObj',
		'AfterPackagesLoad' => 'DefineAllConstants'
	),
	'Memcache' => array(
		'Language' => -1
	)
);
