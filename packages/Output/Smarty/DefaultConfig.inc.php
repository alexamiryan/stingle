<?
$defaultConfig = array(	'AuxConfig' => array(	'compileDir' => "cache/templates_compile/",
												'cacheDir' => "cache/templates_cache/",
												'templateDir' => "view/",
												'defaultRelativeTemplatesPath' => "templates/",
												'defaultRelativeTplPath' => "tpl/",
												'defaultLayout' => "clean",
												'defaultPluginsDir' => __DIR__ . '/Plugins/',
												
												'errorPage' => "error/error",
												'error404Page' => "error/404",
												'exceptionPage' => "error/exception",

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
?>