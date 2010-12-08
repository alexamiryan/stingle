<?
class UsersChatManager extends ChatManager
{
	protected function getChatUser($userId){
		$chatUser = parent::getChatUser($userId);
		
		$chatUser->userName = Reg::get(ConfigManager::getConfig("Users","Users")->Objects->UserManagement)->getLoginById($userId);
		$chatUser->userPhoto = getDefaultPhoto($userId, 'small');;
		
		return $chatUser;
	}
}
?>