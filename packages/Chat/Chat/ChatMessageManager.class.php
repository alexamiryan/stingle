<?php
class ChatMessageManager extends Filterable
{
	const TBL_CHAT_MESSAGES = 'chat_messages';
	
	const IS_SYSTEM_YES = 1;
	const IS_SYSTEM_NO = 0;
	
	const FILTER_ID_FIELD = "id";
	const FILTER_SENDER_USER_ID_FIELD = "sender_user_id";
	const FILTER_RECEIVER_USER_ID_FIELD = "receiver_user_id";
	const FILTER_DATETIME_FIELD = "datetime";
	const FILTER_MESSAGE_FIELD = "message";
	const FILTER_IS_SYSTEM_FIELD = "read";
	

	private $logMinutes = 30;  // in minutes
	
	public function __construct(Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
		if(isset($config->logMinutes)){
			$this->logMinutes = $config->logMinutes;
		}
	}
	
	protected function getFilterableFieldAlias($field){
		switch($field){
			case self::FILTER_ID_FIELD :
			case self::FILTER_SENDER_USER_ID_FIELD :
			case self::FILTER_RECEIVER_USER_ID_FIELD :
			case self::FILTER_DATETIME_FIELD :
			case self::FILTER_MESSAGE_FIELD :
			case self::FILTER_IS_SYSTEM_FIELD :
				return "chat_messages";
		}
		
		throw new RuntimeException("Specified field does not exist or not filterable");
	}
	
	public function getLogMinutes(){
		return $this->logMinutes;
	}
	
	/**
	 * Insert ChatMessage object to database 
	 *
	 * @param ChatMessage $chatMessage
	 * @return int inserted message Id
	 */
	public function insertMessage(ChatMessage $chatMessage){
		if(empty($chatMessage->senderUser->userId) or !is_numeric($chatMessage->senderUser->userId)){
			throw new InvalidArgumentException("Invalid senderUser specified!");
		}
		if(empty($chatMessage->receiverUser->userId) or !is_numeric($chatMessage->receiverUser->userId)){
			throw new InvalidArgumentException("Invalid receiverUser specified!");
		}
		
		$this->query->exec("	INSERT INTO `".Tbl::get('TBL_CHAT_MESSAGES')."`
										(
											`sender_user_id`, 
											`receiver_user_id`, 
											`message`, 
											`is_system`) 
								VALUES	(
											'{$chatMessage->senderUser->userId}', 
											'{$chatMessage->receiverUser->userId}', 
											'{$chatMessage->message}', 
											'{$chatMessage->is_system}'
										)");
		
		return $this->query->getLastInsertId();
	}
	
	public function getLastId(){
		$lastId = $this->query->exec("SELECT MAX(id) as `lastId` FROM `".Tbl::get('TBL_CHAT_MESSAGES')."`")->fetchField('lastId');
		return (empty($lastId) ? 0 : $lastId);
	}
	
	
	public function getChatMessages(ChatMessageFilter $filter = null, MysqlPager $pager = null, $cacheMinutes = 0){
		$chatMessages = array();
		
		if($filter == null){
			$filter = new ChatMessageFilter();
		}
		
		$sqlQuery = "SELECT `chat_messages`.*
						FROM `".Tbl::get('TBL_CHAT_MESSAGES')."` `chat_messages`
						{$this->generateJoins($filter)}
						WHERE 1
						{$this->generateWhere($filter)}
						{$this->generateOrder($filter)}
						{$this->generateLimits($filter)}";
		
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$this->query->exec($sqlQuery, $cacheMinutes);
		}
		if($this->query->countRecords()){
			while(($messageRow = $this->query->fetchRecord()) != null){
				array_push($chatMessages, $this->getChatMessage($messageRow));
			}
		}
		
		return $chatMessages;
	}
	
	protected function getChatMessage($messageRow){
		$chatMessage = new ChatMessage();
		$chatMessage->id = $messageRow['id'];
		$chatMessage->senderUser = ChatUser::getObject($messageRow['sender_user_id']);
		$chatMessage->receiverUser = ChatUser::getObject($messageRow['receiver_user_id']);
		$chatMessage->datetime = $messageRow['datetime'];
		$chatMessage->message = nl2br(htmlentities($messageRow['message'],ENT_COMPAT,'UTF-8'));
		$chatMessage->is_system = $messageRow['is_system'];
		
		return $chatMessage;
	}
}
?>