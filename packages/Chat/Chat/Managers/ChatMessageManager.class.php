<?php
class ChatMessageManager extends DbAccessor
{
	const TBL_CHAT_MESSAGES = 'chat_messages';
	
	const IS_SYSTEM_YES = 1;
	const IS_SYSTEM_NO = 0;
	
	private $logMinutes = 30;  // in minutes
	
	public function __construct(Config $config, $instanceName = null){
		parent::__construct($instanceName);
		
		if(isset($config->logMinutes)){
			$this->logMinutes = $config->logMinutes;
		}
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
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_CHAT_MESSAGES'))
			->values(array(
							"sender_user_id" => $chatMessage->senderUser->userId,
							"receiver_user_id" => $chatMessage->receiverUser->userId,
							"message" => $chatMessage->message,
							"is_system" => $chatMessage->is_system
			));
		$this->query->exec($qb->getSQL());
		
		return $this->query->getLastInsertId();
	}
	
	public function getLastId(){
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->max(new Field('id'), 'lastId'))
			->from(Tbl::get('TBL_CHAT_MESSAGES'));
		$lastId = $this->query->exec($qb->getSQL())->fetchField('lastId');
		return (empty($lastId) ? 0 : $lastId);
	}
	
	
	public function getChatMessages(ChatMessageFilter $filter = null, MysqlPager $pager = null, $cacheMinutes = 0){
		$chatMessages = array();
		
		if($filter == null){
			$filter = new ChatMessageFilter();
		}
		
		$sqlQuery = $filter->getSQL();
		
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
