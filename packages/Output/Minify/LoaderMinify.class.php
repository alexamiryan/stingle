<?php
class LoaderMinify extends Loader{
	protected function includes(){
		$precompileCode = 'define("MINIFY_PATH", "'.dirname(__FILE__) . '/lib/");';
		
		stingleInclude ('lib/Minify/Loader.php', $precompileCode, 'Minify_Loader::register();');
		stingleInclude ('Managers/MinifyWrapper.class.php');
		stingleInclude ('Managers/MinifySmartyWrapper.class.php');
		
		Minify_Loader::register();
	}
	
	protected function loadMinifySmarty(){
		$this->register(new MinifySmartyWrapper());
	}
	
}
