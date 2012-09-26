<?
$defaultConfig = array(	
						'AuxConfig' => array(	'firstLevelDefaultValue' => 'home',
												'actionName' => 'action',
												'validationRegExp' => '/^[a-zA-Z0-9_\-]+$/',
												'modulesDir' => 'modules'),
		
						'Objects' => array(	'RequestParser' => 'requestParser'  ),
						'ObjectsIgnored' => array(	'Nav' => 'nav'  ),
						'Hooks' => array(	'RequestParser' => 'Parse', 'Controller' => 'ExecController'  )
					);
?>