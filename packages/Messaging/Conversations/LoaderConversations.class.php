<?
class LoaderConversations extends Loader{
	protected function includes(){
		require_once ('Objects/Conversation.class.php');
		require_once ('Objects/ConversationMessage.class.php');
		require_once ('Objects/ConversationAttachment.class.php');
		require_once ('Filters/ConversationFilter.class.php');
		require_once ('Filters/ConversationMessagesFilter.class.php');
		require_once ('Filters/ConversationAttachmentFilter.class.php');
		require_once ('Managers/ConversationManager.class.php');
		require_once ('Managers/ConversationAttachmentManager.class.php');
		require_once ('Exceptions/ConversationException.class.php');
		require_once ('Exceptions/ConversationNotUniqueException.class.php');
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
?>