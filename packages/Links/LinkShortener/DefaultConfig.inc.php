<?php
$defaultConfig = array(
	'AuxConfig'=>array(
		'shortenerUrl' => 'l/[linkId]',
		'shortenerUrlRegex' => '\/l\/(.+)\/',
		'shortenerHandlerPath' => 'link/linkId:[linkId]'
	),
    'Objects' => array(
		'LinkShortener' => 'linkShortener'
    ),
	'Hooks' => array(
		'BeforeRequestParser' => 'ParseLinks'
	),
    'Tables' => [
        'links' => 1
    ]
);
