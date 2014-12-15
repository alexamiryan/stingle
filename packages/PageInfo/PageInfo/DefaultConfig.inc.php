<?php
$defaultConfig = array(	
						'Objects' => array(	'PageInfo' => 'pageInfo'  ),
						'Hooks'=> array('AfterRequestParser'=>'SetPageInfo'),
						'Memcache' => array(  'PageInfo' => -1, 'PageInfoManager' => -1  )
					);
