<?php

$defaultConfig = [
	'AuxConfig' => [
		'registerUserObjectFromToken' => false,
		'tokenPlace' => 'post',
		'tokenName' => 'token'
	],
	'Objects' => [
		'UserSessions' => 'userSess',
	],
	'Hooks' => [
		'AfterPackagesLoad' => 'GetUserFromToken'
	],
];
