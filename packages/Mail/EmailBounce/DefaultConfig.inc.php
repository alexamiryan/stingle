<?php

$defaultConfig = array(
	'AuxConfig' => array(
		'IMAP' => array(
			'default' => array(
				'host' => false,
				'port' => null,
				'username' => null,
				'password' => null
			)
		),
		'deleteBouncedEmails' => true,
		'bounceLogging' => true,
		'bounceEchoOutput' => true
	),
	'Objects' => array(
		'BounceHandler' => 'bounceHandler',
	)
);
