<?
$defaultConfig = array(	
						'AuxConfig' => array(	'sessionVarName' => 'user',
												'loginCookieName' => 'login-cookie',
												'rememberDaysCount' => 30,
												'userPropertiesMap' => array(),
												'siteSalt' => 'o&&Y@J5l2t]7M}#@zWuCzQXlx0U1NZ:x%`EYH;="6j1q(?_"sDxZ;]|:Fv>3f(K',
												'pbdkf2IterationCount' => 1024
								),

						'Objects' => array(		'UserManager' => 'userMgr',
												'UserGroupsManager' => 'userGroupsMgr',
												'UserPermissionsManager' => 'userPermsMgr',
												'UserAuthorization' => 'userAuth'),
						'ObjectsIgnored' => array(  'User' => 'usr'  ),
						'Hooks' => array(	'AfterPackagesLoad' => 'UserAuthorization'  ),
						'Memcache' => array(  'UserManager' => 360  )
					);
?>