<?
$defaultConfig = array(	'enabled' => true,
						'host' => '127.0.0.1',
						'port' => "11211",
						'enabled' => false,
						'Objects' => array("query" => "sql"),
						'hooks' => array(  'AfterPluginInit' => 'AddMemcacheTimeConfig'  )
					);
?>