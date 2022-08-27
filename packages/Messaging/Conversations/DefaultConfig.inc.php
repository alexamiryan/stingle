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
	),
    'Tables' => [
        'conversation_attachments' => 1,
        'conversation_messages' => 1,
        'conversation_messages_props' => 1,
        'conversations' => 1
    ]
);
