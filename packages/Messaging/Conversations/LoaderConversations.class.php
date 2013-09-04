<?php
class LoaderConversations extends Loader{
	protected function includes(){
		stingleInclude ('Objects/Conversation.class.php');
		stingleInclude ('Objects/ConversationMessage.class.php');
		stingleInclude ('Objects/ConversationAttachment.class.php');
		stingleInclude ('Objects/ConversationComet.class.php');
		stingleInclude ('Objects/ConversationEventComet.class.php');
		stingleInclude ('Filters/ConversationFilter.class.php');
		stingleInclude ('Filters/ConversationMessagesFilter.class.php');
		stingleInclude ('Filters/ConversationAttachmentFilter.class.php');
		stingleInclude ('Managers/ConversationManager.class.php');
		stingleInclude ('Managers/ConversationAttachmentManager.class.php');
		stingleInclude ('Exceptions/ConversationException.class.php');
		stingleInclude ('Exceptions/ConversationNotUniqueException.class.php');
		stingleInclude ('Exceptions/ConversationNotExistException.class.php');
		stingleInclude ('Exceptions/ConversationNotOwnException.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('ConversationManager');
		Tbl::registerTableNames('ConversationAttachmentManager');
	}
	
	protected function loadConversationManager(){
		$this->register(new ConversationManager());
	}
	
	protected function loadConversationAttachmentManager(){
		$this->register(new ConversationAttachmentManager($this->config->AuxConfig));
	}
}
