<?php
class UsersChatUser extends ChatUser
{
	public $userName;
	public $userPhoto;
	
	public function __construct($userId){
		parent::__construct($userId);
		
		$this->userName = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManagement)->getLoginById($userId);
		$this->userPhoto = getDefaultPhoto($userId, 'small');;
	}
}
?>