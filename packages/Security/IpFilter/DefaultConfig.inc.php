<?
$defaultConfig = array(
						'Objects' => array(	'IpFilterManager' => 'ipFilterMgr' ),
						'Hooks' => array(	'AfterPackagesLoad' => 'CheckForBlockedHost'),
						'Memcache' => array(  'IpFilter' => -1 )
					  );
?>