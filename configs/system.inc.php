<?php

$SYSCONFIG['Stingle'] = [
	'BootCompiler' => true,
	'AllowanceTablesCache' => true,
	'CoreCachePath' => 'cache/stingle_cache/',
	'disabledErrors' => array(E_DEPRECATED),
	'autostartSession' => true,
	'sessionCookieParams' => [
		'secure' => true,
		'httponly' => true,
		'samesite' => 'Strict'
	],
	'autoObStart' => true,
	'siteName' => 'site',
	'errorReporting' => E_ALL ^ E_NOTICE,
    'suppressRemoteAddrInExceptions' => false
];
