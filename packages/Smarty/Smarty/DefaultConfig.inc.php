<?
$defaultConfig = array(	'AuxConfig' => array(	'compileDir' => "cache/templates_compile/",
												'cacheDir' => "cache/templates_cache/",
												'templateDir' => "view/",
												'defaultRelativeTemplatesPath' => "templates/",
												'defaultRelativeTplPath' => "tpl/",
												'defaultLayout' => "clean",
												'defaultPluginsDir' => __DIR__ . '/Plugins/',
												
												'errorsModule' => "error",
												'errorPage' => "error",
												'error404Page' => "404",
												'exceptionPage' => "exception",

												'templatesConfig' => array(
																			'defaultTemplateName' => "default",
																			// TemplateName => Extends
																			'templates' => array(	'default'  => '' )
																		)
												),

						'Objects' => array(	'Smarty' => 'smarty'  ),
						'Hooks' => array(  	'AfterRequestParser' => 'SmartyInit', 
											'InitEnd' => 'SmartyDisplay',
											'AfterPluginInit' => 'RegisterSmartyPlugins'  )
				
					);
?>