<?php

$defaultConfig = array(
	'AuxConfig' => array(
		'clickUrl' => 'mail/click/',
		'shortenLinks' => false,
		'doNotSendEmailForSoftBouncedInLastXDays' => 30,
		'doNotSendEmailForBlockBouncedInLastXDays' => 7,
		'keepStatsForXDays' => 60
	),
	'Objects' => array(
		'EmailStats' => 'emailStats'
	),
	'Hooks' => array(
		'BeforeEmailSend' => 'AddEmailStat',
		'EmailBounce' => 'RecordBounce',
		'IsMailSendAllowed' => 'IsMailSendAllowed'
	),
    'Tables' => [
        'email_stats' => 1
    ]
);
