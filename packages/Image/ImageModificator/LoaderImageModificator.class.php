<?php
class LoaderImageModificator extends Loader{
	protected function includes(){
		stingleInclude ('Managers/ImageModificator.class.php');
		stingleInclude ('Exceptions/ImageModificatorException.class.php');
		stingleInclude ('Helpers/functions.inc.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('ImageModificator');
	}
	
	protected function loadImageModificator(){
		$this->register(new ImageModificator($this->config->AuxConfig));
	}
}
