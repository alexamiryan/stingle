<?php
$defaultConfig = array(
	'AuxConfig'=>array(
		'clickUrl' => 'mail/click/',
		'shortenLinks' => false
	),
    'Objects' => array(
		'EmailStats' => 'emailStats'
    ),
	'Hooks' => array(
			'BeforeEmailSend' => 'AddEmailStat',
			'EmailBounce' => 'RecordBounce',
	)
);
