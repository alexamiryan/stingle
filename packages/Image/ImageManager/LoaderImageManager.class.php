<?
class LoaderImageManager extends Loader{
	protected function includes(){
		require_once ('ImageManager.class.php');
	}
	
	protected function loadImageManager(){
		Reg::register($this->config->Objects->ImageManager, new ImageManager($this->config));
	}
}
?>