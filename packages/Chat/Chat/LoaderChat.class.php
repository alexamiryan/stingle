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
		require_once ('ChatInvitationException.class.php');
		require_once ('ChatSessionException.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('ChatInvitationManager');
		Tbl::registerTableNames('ChatMessageManager');
		Tbl::registerTableNames('ChatSessionManager');
	}
	
	protected function loadChatInvitationManager(){
		$this->register(new ChatInvitationManager($this->config->AuxConfig));
	}
	protected function loadChatMessageManager(){
		$this->register(new ChatMessageManager($this->config->AuxConfig));
	}
	protected function loadChatSessionManager(){
		$this->register(new ChatSessionManager($this->config->AuxConfig));
	}
}
?>