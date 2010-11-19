<?
$defaultConfig = array(	
						'Nav' => 'nav',
						'firstLevelName' => 'module',
						'secondLevelName' => 'page',
						'firstLevelDefaultValue' => 'home',
						'secondLevelDefaultValue' => 'home',
						'actionName' => 'action',
						'validationRegExp' => '/^[a-zA-Z0-9_\-]+$/',

						'modulesDir' => 'modules',
						
						'Objects' => array(	'RequestParser' => 'requestParser'  ),
						'hooks' => array(	'RequestParser' => 'Parse', 'Controller' => 'ExecController'  )
					);
?>