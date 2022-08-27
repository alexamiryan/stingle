<?php

$defaultConfig = array(
	'Objects' => array(
		'IpFilterManager' => 'ipFilterMgr'
	),
	'Hooks' => array(
		'AfterPackagesLoad' => 'CheckForBlockedHost'
	),
	'Memcache' => array(
		'IpFilter' => -1
	),
    'Tables' => [
        'security_blacklisted_countries' => 1,
        'security_blacklisted_ips' => 1,
        'security_whitelisted_ips' => 1
    ]
);
