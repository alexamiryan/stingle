<?
class LoaderChatUsers extends Loader{
	
	protected function includes(){
		require_once ('UsersChatUser.class.php');
	}
	
	protected function customInitBeforeObjects(){
		ConfigManager::addConfig(array('Chat', 'Chat'), 'chatUserClassName', 'UsersChatUser');
	}
}
?>