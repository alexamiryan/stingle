<?
class LoaderChat extends Loader{
	
	protected function includes(){
		require_once ('ChatMessage.class.php');
		require_once ('ChatMessageFilter.class.php');
		require_once ('ChatInvitation.class.php');
		require_once ('ChatResponse.class.php');
		require_once ('ChatUser.class.php');
		require_once ('ChatManager.class.php');
		require_once ('ChatSession.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('ChatManager');
	}
	
	protected function loadChatManager(){
		Reg::register($this->config->Objects->ChatManager, new ChatManager());
	}
}
?>