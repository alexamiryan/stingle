<?php
$defaultConfig = array(
	'AuxConfig' => array(
		'defaultMailConfig' => 'default',
		'mailParams' => array(
			'default' => array(
				'fromMail' => 'no-reply@example.com',
				'fromName' => 'My Website',
				'replyToMail' => '',
				'replyToName' => '',
				'returnPath' => null,
				'transport' => 'PHPMailTransport',
				'transportConfigName' => null
			)
		),
		'mailTemplatesPath' => 'mails/contents/',
		'unsubscribePath' => 'action:unsubscribe',
		'unsubscribeFromAll' => false,
		'isMailsAreBulk' => true,
		'logBounces' => true
			
	),
    'Objects' => array(
		'Mail' => 'mail'
    ),
	'Hooks' => array(
		'EmailBounce' => 'EmailBounce'
	)
);
