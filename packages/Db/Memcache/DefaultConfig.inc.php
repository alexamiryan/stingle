<?
$defaultConfig = array(	'AuxConfig' => array(	'enabled' => true,
												'host' => '127.0.0.1',
												'port' => "11211",
												'enabled' => false),
						'Objects' => array("Query" => "sql"),
						'Hooks' => array(  'BeforePluginInit' => 'AddMemcacheTimeConfig'  )
					);
?>