<?php
class LoaderUserProfile extends Loader{
	protected function includes(){
		require_once ('Managers/UserProfile.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('UserProfile');
	}
}
