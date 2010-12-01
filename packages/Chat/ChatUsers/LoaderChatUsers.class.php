<?
class LoaderChatUsers extends Loader{
	
	protected function includes(){
		require_once ('UsersChatManager.class.php');
		require_once ('UsersChatUser.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('UsersChatManager');
	}
	
	protected function loadChatManager(){
		Reg::register($this->config->Objects->ChatManager, new UsersChatManager());
	}
}
?>