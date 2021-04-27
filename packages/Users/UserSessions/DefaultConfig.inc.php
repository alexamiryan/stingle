<?php

$defaultConfig = [
	'AuxConfig' => [
		'registerUserObjectFromToken' => false,
		'tokenPlace' => 'post',
		'tokenName' => 'token',
        'autoUpdateLastUpdateDate' => true
	],
	'Objects' => [
		'UserSessions' => 'userSess',
	],
	'Hooks' => [
		'AfterPackagesLoad' => 'GetUserFromToken'
	],
];
