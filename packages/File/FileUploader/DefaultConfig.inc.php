<?php

$defaultConfig = array(
	'AuxConfig' => array(
		'uploadDir' => 'uploads/',
		'storageProvider' => 'filesystem',
		'S3Config' => [
			'configName' => 'default',
			'path' => 'uploads/',
			'acl' => 'private'
		]
	)
);
