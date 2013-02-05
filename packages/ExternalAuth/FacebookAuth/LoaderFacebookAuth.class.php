<?php
class LoaderFacebookAuth extends Loader{
	
	protected function includes(){
		require_once ('FacebookAuth.class.php');
		require_once ('FacebookPhotoAlbum.class.php');
		require_once ('FacebookPhoto.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('FacebookAuth');
	}
	
	protected function loadFacebookAuth(){	
		$fbAuth = new FacebookAuth($this->config->AuxConfig);
		$this->register($fbAuth);
	}
}
