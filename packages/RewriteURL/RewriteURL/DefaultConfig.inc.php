<?
$defaultConfig = array(	
						'AuxConfig' => array(	'handler_script' => "index.php",
												'enable_url_rewrite' => 'ON',
												'source_link_style' => 'nice',
												'output_link_style' => 'nice',
												'site_path' => '/'),
						'Objects' => array(	'rewriteURL' => 'rewriteURL'  ),
						'Hooks' => array(  'BeforeRequestParserStep2' => 'ParseURL'  )
					);
?>