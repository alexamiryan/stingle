<?php
class LoaderChat extends Loader{
	
	protected function includes(){
		stingleInclude ('Objects/ChatInvitation.class.php');
		stingleInclude ('Objects/ChatMessage.class.php');
		stingleInclude ('Objects/ChatSession.class.php');
		stingleInclude ('Objects/ChatSessionLog.class.php');
		stingleInclude ('Objects/ChatUser.class.php');
		stingleInclude ('Filters/ChatInvitationsFilter.class.php');
		stingleInclude ('Filters/ChatMessageFilter.class.php');
		stingleInclude ('Filters/ChatSessionFilter.class.php');
		stingleInclude ('Managers/ChatInvitationManager.class.php');
		stingleInclude ('Managers/ChatMessageManager.class.php');
		stingleInclude ('Managers/ChatSessionManager.class.php');
		stingleInclude ('Exceptions/ChatInvitationException.class.php');
		stingleInclude ('Exceptions/ChatSessionException.class.php');
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
