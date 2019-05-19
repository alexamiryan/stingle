<?php

$defaultConfig = array(
	'AuxConfig' => array(
		'clickUrl' => 'mail/click/',
		'shortenLinks' => false,
		'doNotSendEmailForSoftBouncedInLastXDays' => 30
	),
	'Objects' => array(
		'EmailStats' => 'emailStats'
	),
	'Hooks' => array(
		'BeforeEmailSend' => 'AddEmailStat',
		'EmailBounce' => 'RecordBounce',
		'IsMailSendAllowed' => 'IsMailSendAllowed'
	)
);
