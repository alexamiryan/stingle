<?
class ConversationManager extends DbAccessor{
	
	const FILTER_CONVERSATION_ID = 'id';
	const FILTER_CONVERSATION_UUID = 'uuid';
	
	public function __construct($dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
	}
	
}
?>