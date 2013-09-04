<?php
class LoaderUsersMemcache extends Loader{
	protected function includes(){
		stingleInclude ('Managers/UserManagerCaching.class.php');
	}
	
	protected function loadUserManagerCaching(){
		$this->register(new UserManagerCaching(ConfigManager::getConfig("Users", "Users")->AuxConfig));
	}
}
