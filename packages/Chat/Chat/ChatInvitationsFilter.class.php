<?
class ChatInvitationsFilter extends Filter {
	
	public function setId($id, $match = Filter::MATCH_EQUAL){
		if(!is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		
		$this->setCondition(ChatInvitationManager::FILTER_ID_FIELD, $match, $id);
		return $this;
	}
	
	public function setSenderUserId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer");
		}
		
		$this->setCondition(ChatInvitationManager::FILTER_SENDER_USER_ID_FIELD, Filter::MATCH_EQUAL, $id);
		return $this;
	}
	
	public function setReceiverUserId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer");
		}
		
		$this->setCondition(ChatInvitationManager::FILTER_RECEIVER_USER_ID_FIELD, Filter::MATCH_EQUAL, $id);
		return $this;
	}
	
	public function setInvitationDate($time, $match = Filter::MATCH_GREATER){
		if(empty($time)){
			throw new InvalidArgumentException("\$time have to be non empty string");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		$this->setCondition(ChatInvitationManager::FILTER_DATE_FIELD, $match, $time);
		return $this;
	}
	
	public function setInvitationStatus($status, $match = Filter::MATCH_EQUAL){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$status have to be non zero integer");
		}
		if(!in_array($status, ChatInvitationManager::getConstsArray("STATUS"))){
			throw new InvalidIntegerArgumentException("Invalid \$status specified");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		
		$this->setCondition(ChatInvitationManager::FILTER_STATUS_FIELD, $match, $status);
		return $this;
	}
	
	public function setEitherUserId($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer");
		}
		
		$this->setCustomWhere("(`inv`.`sender_user_id`='$userId' OR `inv`.`receiver_user_id`='$userId')");
		return $this;
	}
	
	public function setEitherInterlocutors($userId1, $userId2){
		if(empty($userId1) or !is_numeric($userId1)){
			throw new InvalidIntegerArgumentException("\$userId1 have to be non zero integer");
		}
		if(empty($userId2) or !is_numeric($userId2)){
			throw new InvalidIntegerArgumentException("\$userId2 have to be non zero integer");
		}
		
		$this->setCustomWhere("
								(
									`inv`.`sender_user_id`='$userId1' 
									AND 
									`inv`.`receiver_user_id` = '$userId2'
								)
								OR
								(
									`inv`.`sender_user_id`='$userId2' 
									AND 
									`inv`.`receiver_user_id` = '$userId1'
								)
							");
		return $this;
	}
}
?>