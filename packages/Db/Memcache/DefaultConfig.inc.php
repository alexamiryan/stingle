<?php
$defaultConfig = array(	'AuxConfig' => array(	'enabled' => false,
												'host' => '127.0.0.1',
												'port' => "11211",
												'keyPrefix' => ""),
						'Objects' => array("Query" => "sql"),
						'Hooks' => array(  'BeforePluginInit' => 'AddMemcacheTimeConfig'  )
					);
