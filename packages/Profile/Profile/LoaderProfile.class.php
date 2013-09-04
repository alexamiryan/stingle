<?php
class LoaderProfile extends Loader{
	protected function includes(){
		stingleInclude ('Managers/Profile.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('Profile');
	}
}
