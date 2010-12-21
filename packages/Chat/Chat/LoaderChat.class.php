<?
class LoaderChat extends Loader{
	
	protected function includes(){
		require_once ('ChatInvitation.class.php');
		require_once ('ChatInvitationManager.class.php');
		require_once ('ChatInvitationsFilter.class.php');
		require_once ('ChatMessage.class.php');
		require_once ('ChatMessageFilter.class.php');
		require_once ('ChatMessageManager.class.php');
		require_once ('ChatSession.class.php');
		require_once ('ChatSessionFilter.class.php');
		require_once ('ChatSessionManager.class.php');
		require_once ('ChatUser.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('ChatInvitationManager');
		Tbl::registerTableNames('ChatMessageManager');
		Tbl::registerTableNames('ChatSessionManager');
	}
	
	protected function loadChatInvitationManager(){
		Reg::register($this->config->Objects->ChatInvitationManager, new ChatInvitationManager($this->config));
	}
	protected function loadChatMessageManager(){
		Reg::register($this->config->Objects->ChatMessageManager, new ChatMessageManager($this->config));
	}
	protected function loadChatSessionManager(){
		Reg::register($this->config->Objects->ChatSessionManager, new ChatSessionManager($this->config));
	}
}
?>