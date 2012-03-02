<?
class LoaderImageCache extends Loader{
	protected function includes(){
		require_once ('ImageCache.class.php');
	}
	
	protected function loadImageCache(){
		$this->register(new ImageCache($this->config->AuxConfig));
	}
}
?>