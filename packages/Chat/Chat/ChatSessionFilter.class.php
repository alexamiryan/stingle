<?
class ChatSessionFilter extends Filter {
	
	public function setId($id, $match = Filter::MATCH_EQUAL){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		
		$this->setCondition(ChatSessionManager::FILTER_ID_FIELD, $match, $id);
		return $this;
	}
	
	public function setInviterUserId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer");
		}
		
		$this->setCondition(ChatSessionManager::FILTER_INVITER_USER_ID_FIELD, Filter::MATCH_EQUAL, $id);
		return $this;
	}
	
	public function setInvitedUserId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer");
		}
		
		$this->setCondition(ChatSessionManager::FILTER_INVITED_USER_ID_FIELD, Filter::MATCH_EQUAL, $id);
		return $this;
	}
	
	public function setSessionStartDate($time, $match = Filter::MATCH_GREATER){
		if(empty($time)){
			throw new InvalidArgumentException("\$time have to be non empty string");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		$this->setCondition(ChatSessionManager::FILTER_DATE_FIELD, $match, $time);
		return $this;
	}
	
	public function setSessionCloseDate($time, $match = Filter::MATCH_GREATER){
		if(empty($time)){
			throw new InvalidArgumentException("\$time have to be non empty string");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		$this->setCondition(ChatSessionManager::FILTER_CLOSED_DATE_FIELD, $match, $time);
		return $this;
	}
	
	public function setSessionClosedStatus($status, $match = Filter::MATCH_EQUAL){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$status have to be non zero integer");
		}
		if(!in_array($status, ChatSessionManager::getConstsArray("CLOSED_STATUS"))){
			throw new InvalidIntegerArgumentException("Invalid \$status specified");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		
		$this->setCondition(ChatSessionManager::FILTER_CLOSED_FIELD, $match, $status);
		return $this;
	}
	
	public function setSessionClosedReason($reason, $match = Filter::MATCH_EQUAL){
		if(!is_numeric($reason)){
			throw new InvalidIntegerArgumentException("\$status have to be non zero integer");
		}
		if(!in_array($reason, ChatSessionManager::getConstsArray("CLOSED_REASON"))){
			throw new InvalidIntegerArgumentException("Invalid \$status specified");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		
		$this->setCondition(ChatSessionManager::FILTER_CLOSED_REASON_FIELD, $match, $reason);
		return $this;
	}
	
	public function setEitherUserId($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer");
		}
		
		$this->setCustomWhere("(`sess`.`inviter_user_id`='$userId' OR `sess`.`invited_user_id`='$userId')");
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
									`sess`.`inviter_user_id`='$userId1' 
									AND 
									`sess`.`invited_user_id` = '$userId2'
								)
								OR
								(
									`sess`.`inviter_user_id`='$userId2' 
									AND 
									`sess`.`invited_user_id` = '$userId1'
								)
							");
		return $this;
	}
	
	
}
?>