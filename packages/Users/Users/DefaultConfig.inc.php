<?
$defaultConfig = array(	
						'sessionVarName' => 'user',
						'Objects' => array(	'UserManagement' => 'um', 
												'UserAuthorization' => 'userAuth'),
						'ObjectsIgnored' => array(  'User' => 'usr'  ),
						'hooks' => array(	'AfterPackagesLoad' => 'UserAuthorization'  ),
						'memcache' => array(  'UserManagement' => 360  )
					);
?>