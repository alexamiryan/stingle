<?
class ConversationManager extends DbAccessor{
	
	const TBL_CONVERSATIONS = "conversations";
	const TBL_CONVERSATION_MESSAGES = "conversation_messages";
	
	public function __construct($dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
	}
	
	public function sendMessage(){
		
	}
}
?>