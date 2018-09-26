<?php

$defaultConfig = array(
		'Objects' => array('UniversalOutput' => 'uo'),
		'Hooks' => array(
				'AfterRequestParser' => 'SetRequestType',
				'Output' => 'MainOutput'
		)
);
