<?php

$defaultConfig = [
	'AuxConfig' => [
		'imageTypes' => [
			/*'users' => [
				'storageProvider' => 'filesystem',
				'cacheEnabled' => true,
				'uploadDir' => 'uploads/photos/data/',
				'cacheDir' => 'uploads/photos/cache/',
				'preGenerateCacheOnUpload' => true,
				'saveFormat' => 'jpeg',
			 	'cropRatio' => '1:1',
				'minimumSize' => [
					'largeSideMinSize' => 400,
					'smallSideMinSize' => 150
				],
				'minimumSizeStreight' => [
					'minWidth' => 400,
					'minHeight' => 400
				],
				'imageModels' => [
					'usersSmall' => [
						'modify' => [
							'crop' => [
								'ratio' => '1:1',
								'smallSideMinSize' => 110,
								'applyDefaultCrop' => true
							],
							'resize' => [
								'width' => 110,
								'height' => 110
							]
						]
					],
					'usersBig' => [
						'modify' => [
							'resize' => [
								'width' => 1000,
								'height' => 1000
							]
						]
					]
				],
				'acceptedMimeTypes' => [
					'image/gif',
					'image/jpeg',
					'image/pjpeg',
					'image/png'
				],
				'S3Config' => [
					'configName' => 'default',
					'originalFileACL' => 'private',
					'cacheFileACL' => 'public-read',
					'originalFilesPath' => 'photos/',
					'cachePath' => 'cache/'
				]
			]*/
		],
		'defaultCacheEnabled' => true,
		'defaultPreGenerateCacheOnUpload' => true,
		'defaultUploadDir' => 'uploads/photos/data/',
		'defaultCacheDir' => 'uploads/photos/cache/',
		'defaultStorageProvider' => 'filesystem',
		'defaultSaveFormat' => 'jpeg',
		'defaultS3Config' => [
			'configName' => 'default',
			'originalFileACL' => 'private',
			'cacheFileACL' => 'public-read',
			'originalFilesPath' => 'photos/',
			'cachePath' => 'cache/'
		],
		'defaultAcceptedMimeTypes' => [
			'image/gif',
			'image/jpeg',
			'image/pjpeg',
			'image/png'
		]
	]
];
