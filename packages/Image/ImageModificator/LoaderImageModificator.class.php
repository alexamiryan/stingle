<?php
class LoaderImageModificator extends Loader{
	protected function includes(){
		require_once ('Managers/ImageModificator.class.php');
		require_once ('Exceptions/ImageModificatorException.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('ImageModificator');
	}
	
	protected function loadImageModificator(){
		$this->register(new ImageModificator($this->config->AuxConfig));
	}
}
