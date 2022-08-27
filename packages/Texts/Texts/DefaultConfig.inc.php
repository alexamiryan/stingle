<?php
$defaultConfig = array(
    'Objects' => array(
        'TextsGroupManager' => 'textsGrpMgr',
        'TextsManager' => 'textsMgr',
        'TextsValuesManager' => 'textsValMgr',
        'TextsAliasManager' => 'textsAliasMgr'
    ),
    'Memcache' => array(
        'TextsGroupManager' => -1,
        'TextsManager' => -1,
        'TextsValuesManager' => -1,
        'TextsAliasManager' => -1
    ),
    'Tables' => [
        'texts' => 1,
        'texts_aliases' => 1,
        'texts_groups' => 1,
        'texts_values' => 1
    ]
);
