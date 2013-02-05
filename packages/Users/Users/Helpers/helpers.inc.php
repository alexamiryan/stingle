<?php
function isAuthorized(){
	if(Reg::isRegistered(ConfigManager::getConfig("Users", "Users")->ObjectsIgnored->User)){
		return true;
	}
	return false;
}
