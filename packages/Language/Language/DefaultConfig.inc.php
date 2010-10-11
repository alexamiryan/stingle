<?
$defaultConfig = array(	
						'Objects' => array(	'LanguageManager' => 'lm'  ),
						'ObjectsIgnored' => array(  'Language' => 'language'  ),
						'hooks' => array(	'AfterThisPluginTreeInit' => 'GetLanguageObj',
											'AfterPackagesLoad' => 'DefineAllConstants'  ),
						'memcache' => array(  'LanguageManager' => -1, 'Language' => -1  )
					);
?>