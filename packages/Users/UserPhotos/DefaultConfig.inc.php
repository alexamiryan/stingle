<?php

$defaultConfig = array(
	'AuxConfig' => array(
		'maxPhotosCount' => 5,
		'preModeration' => true
	),
	'Objects' => array(
		'UserPhotoManager' => 'photoMgr'
	),
    'Tables' => [
        'users_photos' => 1
    ]
);
