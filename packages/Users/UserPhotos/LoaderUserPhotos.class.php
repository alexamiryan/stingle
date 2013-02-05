<?php
class LoaderUserPhotos extends Loader{
	protected function includes(){
		require_once ('Filters/UserPhotosFilter.class.php');
		require_once ('Exceptions/UserPhotosException.class.php');
		require_once ('Objects/UserPhoto.class.php');
		require_once ('Managers/UserPhotoManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('UserPhotoManager');
	}
	
	protected function loadUserPhotoManager(){
		$this->register(new UserPhotoManager($this->config->AuxConfig));
	}
}
