<?php

$defaultConfig = array(
	'AuxConfig' => array(
		'memcacheEnabled' => true,
		'throwExceptionOnNotFound' => false,
		'defineAllConstsOn' => false,
		'useSession' => true,
		'useCookies' => true
	),
	'Objects' => array(
		'LanguageManager' => 'lm'
	),
	'ObjectsIgnored' => array(
		'Language' => 'language'
	),
	'Hooks' => array(
		'AfterPackagesLoad' => 'DefineAllConstants'
	),
	'Memcache' => array(
		'Language' => -1,
		'LanguageManager' => -1
	),
    'Tables' => [
        'lm_languages' => 1,
        'lm_constants' => 1,
        'lm_values' => 1
    ]
);
