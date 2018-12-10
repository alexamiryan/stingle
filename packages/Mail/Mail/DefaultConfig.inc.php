<?php
$defaultConfig = array(
	'AuxConfig' => array(
		'mailParams' => array(
			'fromMail' => 'no-reply@example.com',
			'fromName' => 'My Website',
			'replyToMail' => '',
			'replyToName' => '',
			'returnPath' => null
		),
		'mailTemplatesPath' => 'mails/contents/',
		'unsubscribePath' => 'action:unsubscribe',
		'unsubscribeFromAll' => false,
		'isMailsAreBulk' => true
			
	),
    'Objects' => array(
		'Mail' => 'mail'
    )
);
