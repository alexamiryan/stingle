<?
class LoaderUserPhotos extends Loader{
	protected function includes(){
		require_once ('UserPhotosFilter.class.php');
		require_once ('UserPhotosException.class.php');
		require_once ('UserPhoto.class.php');
		require_once ('UserPhotoManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('UserPhotoManager');
	}
	
	protected function loadUserPhotoManager(){
		Reg::register($this->config->Objects->UserPhotoManager, new UserPhotoManager($this->config));
	}
}
?>