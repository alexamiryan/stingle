<?php
$defaultConfig = array(	
						'AuxConfig' => array(
								'secondFactorAuthName' => 'yubiKey',
								'yubico_id' =>  '4264',
								'yubico_key' => 'ETbmajX8ozu1h/cqvRvBD28G6A4='),						
						'Hooks' => array(	'OnUserLogin' => 'YubicoAuth'  )
					);
