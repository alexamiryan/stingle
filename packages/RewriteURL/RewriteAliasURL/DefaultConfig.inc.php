<?
$defaultConfig = array(	
						'Objects' => array(	'aliasMap' => 'aliasMap',
											'rewriteAliasURL' => 'rewriteURL'),
						'Hooks' => array(  'BeforeRequestParser' => 'ParseAliases'  ),
						'Memcache' => array(  'RewriteAliasMap' => -1  )
					);
?>