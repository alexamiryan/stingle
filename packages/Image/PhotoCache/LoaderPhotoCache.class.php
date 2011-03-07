<?
class LoaderPhotoCache extends Loader{
	protected function includes(){
		require_once ('PhotoCache.class.php');
	}
	
	protected function loadPhotoCache(){
		Reg::register($this->config->Objects->PhotoCache, new PhotoCache($this->config));
	}
}
?>