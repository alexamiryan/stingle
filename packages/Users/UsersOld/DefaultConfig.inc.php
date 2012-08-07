<?
$defaultConfig = array(	
						'AuxConfig' => array(	'sessionVarName' => 'user',
												'loginCookieName' => 'login-cookie',
												'rememberDaysCount' => 30,
												'bruteForceProtectionEnabled' => true,
												'failedAuthLimit' => 5),

						'Objects' => array(		'UserManagement' => 'um', 
												'UserAuthorization' => 'userAuth'),
						'ObjectsIgnored' => array(  'User' => 'usr'  ),
						'Hooks' => array(	'AfterPackagesLoad' => 'UserAuthorization'  ),
						'Memcache' => array(  'UserManagement' => 360  )
					);
?>