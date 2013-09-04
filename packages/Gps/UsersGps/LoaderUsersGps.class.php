<?php
class LoaderUsersGps extends Loader{
	
	protected function includes(){
		stingleInclude ('Managers/UsersGps.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('UsersGps');
	}
	
	protected function loadUsersGps(){
		$this->register(new UsersGps());
	}
}
