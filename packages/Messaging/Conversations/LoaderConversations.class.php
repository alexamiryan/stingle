<?
class LoaderConversations extends Loader{
	protected function includes(){
		require_once ('Conversation.class.php');
		require_once ('ConversationMessage.class.php');
		require_once ('ConversationManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('ConversationManager');
	}
	
	protected function loadConversationManager(){
		$this->register(new ConversationManager());
	}
}
?>