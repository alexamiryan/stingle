<?php

$defaultConfig = [
	'AuxConfig' => [
		'sessionVarName' => 'user',
		'loginCookieName' => 'login-cookie',
		'rememberDaysCount' => 30,
		'userPropertiesMap' => [],
		'siteSalt' => 'o&&Y@J5l2t]7M}#@zWuCzQXlx0U1NZ:x%`EYH;="6j1q(?_"sDxZ;]|:Fv>3f(K',
		'pbdkf2IterationCount' => 1024,
		'secondFactorOrder' => [],
		'useSessions' => true,
		'useCookies' => true,
		'saveLastLoginDateIP' => true
	],
	'Objects' => [
		'UserManager' => 'userMgr',
		'UserGroupsManager' => 'userGroupsMgr',
		'UserPermissionsManager' => 'userPermsMgr',
		'UserAuthorization' => 'userAuth'
	],
	'ObjectsIgnored' => [
		'User' => 'usr'
	],
	'Hooks' => [
		'AfterPackagesLoad' => 'UserAuthorization'
	],
	'Memcache' => [
		'UserManager' => 360
	]
];
