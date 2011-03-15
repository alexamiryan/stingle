<?
$defaultConfig = array(	
						'AuxConfig' => array(	'dataDir' => 'uploads/photos/data/',
												'cacheDir' => 'uploads/photos/cache/',
												'sizes' => array(
																	'small' => array(
																			'width' => 75,
																			'height' => 75
																		),
																	'medium' =>  array(
																			'width' => 150,
																			'height' => 150
																		),
																	'big' => array(
																			'width' => 400,
																			'height' => 400
																		)
																)),
						'Objects' => array(	'ImageManager' => 'imageMgr'  )
					);
?>