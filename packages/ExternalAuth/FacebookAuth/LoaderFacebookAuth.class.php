<?php
class LoaderFacebookAuth extends Loader{
	
	protected function includes(){
		stingleInclude ('Managers/FacebookAuth.class.php');
		stingleInclude ('Objects/FacebookPhotoAlbum.class.php');
		stingleInclude ('Objects/FacebookPhoto.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('FacebookAuth');
	}
	
	protected function loadFacebookAuth(){	
		$fbAuth = new FacebookAuth($this->config->AuxConfig);
		$this->register($fbAuth);
	}
}
