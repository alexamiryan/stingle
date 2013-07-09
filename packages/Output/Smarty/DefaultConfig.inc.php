<?php
$defaultConfig = array(	'AuxConfig' => array(	'compileDir' => "cache/templates_compile/",
												'cacheDir' => "cache/templates_cache/",
												'templateDir' => "view/",
												'defaultRelativeTemplatesPath' => "templates/",
												'defaultRelativeTplPath' => "tpl/",
												'defaultLayout' => "clean",
												'defaultPluginsDir' => __DIR__ . '/Plugins/',
		
												'caching' => 0,
												'defaultCacheTime' => 3600, // In Seconds
												'compileCheck' => true,
												'memcacheSupport' => false,
												
												'errorPage' => "error/error",
												'error404Page' => "error/404",
												'exceptionPage' => "error/exception",
		
												'urlCounterForClearCache' => null,

												'templatesConfig' => array(
																			'defaultTemplateName' => "default",
																			// TemplateName => Extends
																			'templates' => array(	'default'  => '' )
																		)
												),

						'Objects' => array(	'Smarty' => 'smarty'  ),
						'Hooks' => array(  	
											'AfterPluginInit' => 'CollectSmartyPluginsDir',
											'AfterPackagesLoad' => 'RegisterSmartyPlugins',
											'AfterRequestParser' => 'SmartyInit', 
											'Output' => 'MainOutput'
								  )
				
					);
