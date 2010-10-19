<?
$defaultConfig = array(	
						'Objects' => array(	'aliasMap' => 'aliasMap',
												'rewriteAliasURL' => 'rewriteURL',
												),
						'hooks' => array(  'BeforeRequestParser' => 'ParseAliases'  ),
						'memcache' => array(  'RewriteAliasMap' => -1  )
					);
?>