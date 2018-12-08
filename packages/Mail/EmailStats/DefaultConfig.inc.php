<?php
$defaultConfig = array(
	'AuxConfig'=>array(
		'clickUrl' => 'mail/click/'
	),
    'Objects' => array(
		'EmailStats' => 'emailStats'
    ),
	'Hooks' => array(
			'BeforeEmailSend' => 'AddEmailStat',
			'EmailBounce' => 'RecordBounce',
	)
);
