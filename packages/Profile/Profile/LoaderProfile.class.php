<?php
class LoaderProfile extends Loader{
	protected function includes(){
		require_once ('Profile.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('Profile');
	}
}
