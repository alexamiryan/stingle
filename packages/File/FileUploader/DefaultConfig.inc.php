<?php

$defaultConfig = array(
	'AuxConfig' => array(
		'uploadDir' => 'uploads/',
		'storageProvider' => 'filesystem',
		'S3Config' => [
			'configName' => 'default',
			'path' => 'banner-data/',
			'acl' => 'private'
		]
	)
);
