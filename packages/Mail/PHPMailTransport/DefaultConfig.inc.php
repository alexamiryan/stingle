<?php

$defaultConfig = array(
	'AuxConfig' => array(
		'default' => array(
			'isDefault' => false,
			'DKIM' => array(
				'enabled' => false,
				'publicKey' => null,
				'privateKey' => null,
				'password' => null,
				'domain' => null,
				'selector' => 'stingle'
			),
			'SMTP' => array(
				'enabled' => false,
				'host' => null,
				'port' => 25,
				'secureMethod' => null,
				'debug' => null,
				'customOptions' => null,
				'auth' => array(
					'enabled' => false,
					'username' => null,
					'password' => null
				)
			)
		)
	)
);
