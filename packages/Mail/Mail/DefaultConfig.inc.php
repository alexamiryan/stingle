<?php
$defaultConfig = array(	
	'AuxConfig' => array( 
		'mailParams' => array(
		    'fromMail' => 'no-reply@example.com',
		    'fromName' => 'My Website',
		    'replyToMail' => 'contact@example.com',
		    'replyToName' => 'My Website Support',
		),
		'DKIM' =>array(
		    'enabled' => false,
		    'publicKey' => null,
		    'privateKey' => null,
		    'password' => null,
		    'domain' => null,
		    'selector' => 'stingle'
		)
	),
	'Objects' => array(
	    'Mail' => 'mail'
	)
);
