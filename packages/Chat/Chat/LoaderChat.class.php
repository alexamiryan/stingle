<?php
class LoaderChat extends Loader{
	
	protected function includes(){
		require_once ('Objects/ChatInvitation.class.php');
		require_once ('Managers/ChatInvitationManager.class.php');
		require_once ('Filters/ChatInvitationsFilter.class.php');
		require_once ('Objects/ChatMessage.class.php');
		require_once ('Filters/ChatMessageFilter.class.php');
		require_once ('Managers/ChatMessageManager.class.php');
		require_once ('Objects/ChatSession.class.php');
		require_once ('Filters/ChatSessionFilter.class.php');
		require_once ('Managers/ChatSessionManager.class.php');
		require_once ('Objects/ChatUser.class.php');
		require_once ('Exceptions/ChatInvitationException.class.php');
		require_once ('Exceptions/ChatSessionException.class.php');
		require_once ('Objects/ChatComet.class.php');
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
