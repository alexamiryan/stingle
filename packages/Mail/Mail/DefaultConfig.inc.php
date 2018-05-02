<?php

$defaultConfig = array(
	'AuxConfig' => array(
		'mailParams' => array(
			'fromMail' => 'no-reply@example.com',
			'fromName' => 'My Website',
			'replyToMail' => 'contact@example.com',
			'replyToName' => 'My Website Support',
			'returnPath' => null
		),
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
	),
	'Objects' => array(
		'Mail' => 'mail'
	)
);
