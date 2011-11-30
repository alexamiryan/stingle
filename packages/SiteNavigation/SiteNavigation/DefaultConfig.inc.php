<?
$defaultConfig = array(	
						'AuxConfig' => array(	'firstLevelName' => 'module',
												'secondLevelName' => 'page',
												'firstLevelDefaultValue' => 'home',
												'secondLevelDefaultValue' => 'home',
												'actionName' => 'action',
												'validationRegExp' => '/^[a-zA-Z0-9_\-]+$/',
												'modulesDir' => 'modules'),
						'Objects' => array(	'RequestParser' => 'requestParser'  ),
						'ObjectsIgnored' => array(	'Nav' => 'nav'  ),
						'Hooks' => array(	'RequestParser' => 'Parse', 'Controller' => 'ExecController'  )
					);
?>