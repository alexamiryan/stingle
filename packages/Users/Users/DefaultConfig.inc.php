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
		'saveLastLoginDateIP' => true,
		'sameSiteCookie' => 'Strict'
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
    'Tables' => [
        'wum_groups' => 1,
        'wum_groups_permissions' => 1,
        'wum_permissions' => 1,
        'wum_users' => 1,
        'wum_users_groups' => 1,
        'wum_users_permissions' => 1,
        'wum_users_properties' => 1
    ],
	'Memcache' => [
		'UserManager' => 360
	]
];
