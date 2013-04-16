<?php
class LoaderUsersMemcache extends Loader{
	protected function includes(){
		require_once ('Managers/UserManagerCaching.class.php');
	}
	
}
