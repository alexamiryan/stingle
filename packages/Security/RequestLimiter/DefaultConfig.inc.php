<?
$defaultConfig = array(
						'AuxConfig' => array("requestsLimit" => 150),
						'Hooks' => array(	'BeforeController' => 'RequestLimiterRun'),
						'Objects' => array('RequestLimiter' => 'requestLimiter')
					  );
?>