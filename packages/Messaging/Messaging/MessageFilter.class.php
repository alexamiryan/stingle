<?
class MessageFilter extends Filter {
	
	public function setId($match, $id){
		if(!is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be integer.");
		}
		
		$this->setCondition(MessageManagement::FILTER_ID_FIELD, $match, $id);
		return $this;
	}
	
	public function setStartDate($date){
		if(!is_numeric($date)){
			throw new InvalidTimestampArgumentException("\$date have to be integer.");
		}
		
		$this->setCondition(MessageManagement::FILTER_DATE_FIELD, Filter::MATCH_GREATER_EQUAL, $date);
		return $this;
	}
	
	public function setEndDate($date){
		if(!is_numeric($date)){
			throw new InvalidTimestampArgumentException("\$date have to be integer.");
		}
		
		$this->setCondition(MessageManagement::FILTER_DATE_FIELD, Filter::MATCH_LESS_EQUAL, $date);
		return $this;
	}
		
	public function setSender($senderId){
		if(!is_numeric($senderId)){
			throw new InvalidIntegerArgumentException("\$senderId have to be integer.");
		}
		
		$this->setCondition(MessageManagement::FILTER_SENDER_FIELD, Filter::MATCH_EQUAL, $senderId);
		return $this;
	}
	
	public function setReceiver($receiverId){
		if(!is_numeric($receiverId)){
			throw new InvalidIntegerArgumentException("\$receiverId have to be integer.");
		}
		
		$this->setCondition(MessageManagement::FILTER_RECEIVER_FIELD, Filter::MATCH_EQUAL, $receiverId);
		return $this;
	}
	
	public function setWorker($workerGroupId){
		if(!is_numeric($workerGroupId)){
			throw new InvalidIntegerArgumentException("\$workerGroupId have to be integer.");
		}
		
		$this->setExtraJoin(
			WUM_USERS_GROUPS, "ug", "user_id",
			MessageManagement::FILTER_RECEIVER_FIELD,
			MySqlDatabase::JOIN_LEFT
		);
		$this->setExtraWhere("ug", "group_id", $workerGroupId, Filter::MATCH_EQUAL);
		$this->setExtraWhere("ug", "is_primary", 1, Filter::MATCH_EQUAL);
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
				$this->setFieldsComparison(MessageManagement::FILTER_RECEIVER_FIELD, 
											MessageManagement::FILTER_SENDER_FIELD, 
											Filter::MATCH_NOT_EQUAL);
				$this->setReceiver($userId);
				break;
			case MessageManagement::BOX_SENT :
				$this->setTrashedStatus(MessageManagement::STATUS_TRASHED_UNTRASHED);
				$this->setDeletedStatus(MessageManagement::STATUS_DELETED_UNDELETED);
				$this->setFieldsComparison(MessageManagement::FILTER_RECEIVER_FIELD, 
											MessageManagement::FILTER_SENDER_FIELD, 
											Filter::MATCH_EQUAL);
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
		
		$this->setCondition(MessageManagement::FILTER_READ_FIELD, FIlter::MATCH_EQUAL, $readStatus);
		return $this;
	}
	
	public function setTrashedStatus($trashedStatus){
		if(!is_numeric($trashedStatus)){
			throw new InvalidIntegerArgumentException("\$trashedStatus have to be integer.");
		}
		
		$this->setCondition(MessageManagement::FILTER_TRASHED_FIELD, FIlter::MATCH_EQUAL, $trashedStatus);
		return $this;
	}
	
	public function setDeletedStatus($deletedStatus){
		if(!is_numeric($deletedStatus)){
			throw new InvalidIntegerArgumentException("\$deletedStatus have to be integer.");
		}
		$this->setCondition(MessageManagement::FILTER_DELETED_FIELD, FIlter::MATCH_EQUAL, $deletedStatus);
		return $this;
	}
	
}
?>