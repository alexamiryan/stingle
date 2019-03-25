<?php

$defaultConfig = array(
	'AuxConfig' => array(
		'uploadDir' => 'uploads/attachs/',
		'attachmentsClearTimeout' => 4, // In Days
		'imageMimeTypes' => array(
			'image/gif',
			'image/jpeg',
			'image/pjpeg',
			'image/png'
		),
		'storageProvider' => 'filesystem',
		'S3Config' => [
			'configName' => 'default',
			'acl' => 'private',
			'path' => 'conv-attachs/'
		]
	),
	'Objects' => array(
		'ConversationManager' => 'convMgr',
		'ConversationAttachmentManager' => 'convAttMgr'
	)
);
