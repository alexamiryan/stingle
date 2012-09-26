<?
$defaultConfig = array(	
						'AuxConfig' => array(	
												'enableUrlRewrite' => true,
												'sitePath' => '/',
												'levels' => array(
															'module',
															'page',
															'subpage')
								),
						'Objects' => array(	'rewriteURL' => 'rewriteURL'  ),
						'Hooks' => array(  'BeforeRequestParserStep2' => 'ParseURL'  )
					);
?>