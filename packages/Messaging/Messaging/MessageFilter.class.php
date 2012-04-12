<?
class MessageFilter extends Filter {
	
	public function __construct($headersOnly = true){
		parent::__construct();
		
		if($headersOnly){
			$this->qb->select(new Field("*"));
		}
		else{
			$this->qb->select(array(
					new Field('id', 'main'), 
					new Field('subject', 'main'), 
					new Field('date', 'main'), 
					new Field('sender', 'extra'), 
					new Field('read', 'extra'), 
					new Field('trashed', 'extra'), 
					new Field('deleted', 'extra')));
		}
		
		$this->qb->from(Tbl::get('TBL_MESSAGES', 'MessageManagement'), "main")
			->leftJoin(Tbl::get('TBL_EXTRA', 'MessageManagement'), "extra", 
					$this->qb->expr()->equal(new Field('id', 'main'), new Field('message_id', 'extra')));
	}
	
	public function setSelectCount(){
		$this->qb->select($this->qb->expr()->count(new Field('*'), 'cnt'));
	}
	
	public function setId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", "main"), $id));
		return $this;
	}
	
	public function setStartDate($date){
		if(!is_numeric($date)){
			throw new InvalidTimestampArgumentException("\$date have to be integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->greaterEqual(new Field("date", "main"), $date));
		return $this;
	}
	
	public function setEndDate($date){
		if(!is_numeric($date)){
			throw new InvalidTimestampArgumentException("\$date have to be integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->lessEqual(new Field("date", "main"), $date));
		return $this;
	}
		
	public function setSender($senderId){
		if(!is_numeric($senderId)){
			throw new InvalidIntegerArgumentException("\$senderId have to be integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("sender", "extra"), $senderId));
		return $this;
	}
	
	public function setReceiver($receiverId){
		if(!is_numeric($receiverId)){
			throw new InvalidIntegerArgumentException("\$receiverId have to be integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("receiver", "extra"), $receiverId));
		return $this;
	}
	
	public function setBox($box, $userId){
		if(empty($box)){
			throw new InvalidArgumentException("\$box have to be non empty string.");
		}
		switch ($box){
			case MessageManagement::BOX_INBOX :
				$this->setTrashedStatus(MessageManagement::STATUS_TRASHED_UNTRASHED);
				$this->setDeletedStatus(MessageManagement::STATUS_DELETED_UNDELETED);
				$this->qb->andWhere($this->qb->expr()->notEqual(new Field("receiver", "extra"), new Field("sender", "extra")));
				$this->setReceiver($userId);
				break;
			case MessageManagement::BOX_SENT :
				$this->setTrashedStatus(MessageManagement::STATUS_TRASHED_UNTRASHED);
				$this->setDeletedStatus(MessageManagement::STATUS_DELETED_UNDELETED);
				$this->qb->andWhere($this->qb->expr()->equal(new Field("receiver", "extra"), new Field("sender", "extra")));
				$this->setSender($userId);
				break;
			case MessageManagement::BOX_TRASH :
				$this->setTrashedStatus(MessageManagement::STATUS_TRASHED_TRASHED);
				$this->setDeletedStatus(MessageManagement::STATUS_DELETED_UNDELETED);
				$this->setReceiver($userId);
				break;
			case MessageManagement::BOX_DELETED :
				$this->setDeletedStatus(MessageManagement::STATUS_DELETED_DELETED);
				$this->setReceiver($userId);
				break;
		}
		
	}
	
	public function setReadStatus($readStatus){
		if(!is_numeric($readStatus)){
			throw new InvalidIntegerArgumentException("\$readStatus have to be integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("read", "extra"), $readStatus));
		return $this;
	}
	
	public function setTrashedStatus($trashedStatus){
		if(!is_numeric($trashedStatus)){
			throw new InvalidIntegerArgumentException("\$trashedStatus have to be integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("trashed", "extra"), $trashedStatus));
		return $this;
	}
	
	public function setDeletedStatus($deletedStatus){
		if(!is_numeric($deletedStatus)){
			throw new InvalidIntegerArgumentException("\$deletedStatus have to be integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("deleted", "extra"), $deletedStatus));
		return $this;
	}
	
	public function setOrderDateAsc(){
		$this->setOrder(new Field("date", "main"), MySqlDatabase::ORDER_ASC);
	}
	
	public function setOrderDateDesc(){
		$this->setOrder(new Field("date", "main"), MySqlDatabase::ORDER_DESC);
	}
}
?>