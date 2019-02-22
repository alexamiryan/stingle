<?php
$defaultConfig = array(
	'AuxConfig' => array(
		'mailParams' => array(
			'default' => array(
				'isDefault' => false,
				'fromMail' => 'no-reply@example.com',
				'fromName' => 'My Website',
				'replyToMail' => '',
				'replyToName' => '',
				'returnPath' => null
			)
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
