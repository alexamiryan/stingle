<?php
class LoaderFacebookSDK extends Loader{
	
	protected function includes(){
		stingleInclude ('Facebook/autoload.php');
		//stingleInclude ('Facebook/FacebookRequest.php');
		
		stingleInclude ('Managers/FacebookSDK.class.php');
		stingleInclude ('Objects/FacebookPhotoAlbum.class.php');
		stingleInclude ('Objects/FacebookPhoto.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('FacebookSDK');
	}
}
