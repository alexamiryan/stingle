<?php
$defaultConfig = array(
						'AuxConfig' => array(	"requestsLimit" => 150,
												'loginBruteForceProtectionEnabled' => true,
												'failedLoginLimit' => 5,),
						'Hooks' => array(	'BeforeController' => 'RequestLimiterRun',
											'UserAuthSuccess' => 'ClearInvalidLoginsLog',
											'UserAuthFail' => 'InvalidLoginAttempt'
										),
						'Objects' => array('RequestLimiter' => 'requestLimiter')
					  );
