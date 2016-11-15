<?php
$defaultConfig = array(	
						'AuxConfig' => array(	
												'enableUrlRewrite' => true,
												'sitePath' => '/',
												'levels' => array(
															'module',
															'page',
															'subpage',
															'subpage1',
															'subpage2')
								),
						'Objects' => array(	'rewriteURL' => 'rewriteURL'  ),
						'Hooks' => array(  'BeforeRequestParserStep2' => 'ParseURL'  )
					);
