<?
class LoaderPhotos extends Loader{
	protected function includes(){
		require_once ('PhotoManager.class.php');
	}
	
	protected function loadPhotoManager(){
		$photoMngr = new PhotoManager($this->config);
		Reg::register($this->config->Objects->PhotoManager, $photoMngr);
	}
}
?>