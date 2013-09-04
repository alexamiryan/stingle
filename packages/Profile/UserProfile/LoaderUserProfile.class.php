<?php
class LoaderUserProfile extends Loader{
	protected function includes(){
		stingleInclude ('Managers/UserProfile.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('UserProfile');
	}
}
