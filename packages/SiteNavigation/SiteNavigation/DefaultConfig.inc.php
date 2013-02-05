<?php
$defaultConfig = array(	
						'AuxConfig' => array(	'firstLevelDefaultValue' => 'home',
												'actionName' => 'action',
												'validationRegExp' => '/^[a-zA-Z0-9_\-]+$/',
												'controllersDir' => 'controllers',
												'defaultControllerPath' => 'default'),
		
						'Objects' => array(	'RequestParser' => 'requestParser',
											'Controller' => 'controller'  ),
						'ObjectsIgnored' => array(	'Nav' => 'nav'  ),
						'Hooks' => array(	'RequestParser' => 'Parse', 'Controller' => 'ExecController'  )
					);
