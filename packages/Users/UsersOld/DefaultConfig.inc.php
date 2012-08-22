<?
$defaultConfig = array(	
						'AuxConfig' => array(	'sessionVarName' => 'user',
												'loginCookieName' => 'login-cookie',
												'rememberDaysCount' => 30,
												'bruteForceProtectionEnabled' => true,
												'failedAuthLimit' => 5),

						'Objects' => array(		'UserManager' => 'um', 
												'UserAuthorization' => 'userAuth'),
						'ObjectsIgnored' => array(  'User' => 'usr'  ),
						'Hooks' => array(	'AfterPackagesLoad' => 'UserAuthorization'  ),
						'Memcache' => array(  'UserManager' => 360  )
					);
?>