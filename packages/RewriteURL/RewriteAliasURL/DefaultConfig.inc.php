<?php
$defaultConfig = array(
    'Objects' => array('aliasMap' => 'aliasMap',
        'rewriteAliasURL' => 'rewriteURL'),
    'Hooks' => array('BeforeRequestParser' => 'ParseAliases'),
    'Memcache' => array('RewriteAliasMap' => -1),
    'Tables' => [
        'url_alias' => 1,
        'url_alias_host' => 1
    ]
);
