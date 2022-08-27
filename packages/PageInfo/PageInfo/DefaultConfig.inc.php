<?php
$defaultConfig = array(
    'Objects' => array('PageInfo' => 'pageInfo'),
    'Hooks' => array('AfterRequestParser' => 'SetPageInfo'),
    'Memcache' => array('PageInfo' => -1, 'PageInfoManager' => -1),
    'Tables' => [
        'site_pages_info' => 1
    ]
);
