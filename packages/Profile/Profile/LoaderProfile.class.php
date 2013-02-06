<?php
class LoaderProfile extends Loader{
	protected function includes(){
		require_once ('Managers/Profile.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('Profile');
	}
}
