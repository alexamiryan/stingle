<?
class LoaderImageModificator extends Loader{
	protected function includes(){
		require_once ('ImageModificator.class.php');
		require_once ('ImageModificatorException.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('ImageModificator');
	}
	
	protected function loadImageModificator(){
		$this->register(new ImageModificator($this->config->AuxConfig));
	}
}
?>