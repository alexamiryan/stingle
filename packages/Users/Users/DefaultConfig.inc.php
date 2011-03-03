<?
$defaultConfig = array(	
						'AuxConfig' => array(	'sessionVarName' => 'user',
												'loginCookieName' => 'login-cookie'),
						'Objects' => array(		'UserManagement' => 'um', 
												'UserAuthorization' => 'userAuth'),
						'ObjectsIgnored' => array(  'User' => 'usr'  ),
						'Hooks' => array(	'AfterPackagesLoad' => 'UserAuthorization'  ),
						'Memcache' => array(  'UserManagement' => 360  )
					);
?>