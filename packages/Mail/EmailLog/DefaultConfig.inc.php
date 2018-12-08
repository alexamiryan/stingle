<?php
$defaultConfig = array(
	'AuxConfig'=>array(),
    'Objects' => array(
		'EmailLog' => 'emailLog'
    ),
	'Hooks' => array(
		'EmailBounceByUser' => 'RecordBounceLog',
	)
);
