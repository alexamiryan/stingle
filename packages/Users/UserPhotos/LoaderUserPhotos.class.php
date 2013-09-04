<?php
class LoaderUserPhotos extends Loader{
	protected function includes(){
		stingleInclude ('Filters/UserPhotosFilter.class.php');
		stingleInclude ('Exceptions/UserPhotosException.class.php');
		stingleInclude ('Objects/UserPhoto.class.php');
		stingleInclude ('Managers/UserPhotoManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('UserPhotoManager');
	}
	
	protected function loadUserPhotoManager(){
		$this->register(new UserPhotoManager($this->config->AuxConfig));
	}
}
