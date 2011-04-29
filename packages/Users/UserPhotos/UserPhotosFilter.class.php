<?
class UserPhotosFilter extends Filter {
	
	public function setPhotoId($photo_id, $match = Filter::MATCH_EQUAL){
		if(empty($photo_id) or !is_numeric($photo_id)){
			throw new InvalidIntegerArgumentException("\$photo_id have to be non zero integer");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		
		$this->setCondition(UserPhotoManager::FILTER_USER_PHOTO_ID_FIELD, $match, $photo_id);
		return $this;
	}
	
	public function setUserId($user_id, $match = Filter::MATCH_EQUAL){
		if(empty($user_id) or !is_numeric($user_id)){
			throw new InvalidIntegerArgumentException("\$user_id have to be non zero integer");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		
		$this->setCondition(UserPhotoManager::FILTER_USER_ID_FIELD, $match, $user_id);
		return $this;
	}
	
	public function setApprovedStatus($status){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$state have to be integer");
		}
		
		$this->setCondition(UserPhotoManager::FILTER_APPROVED_FIELD, Filter::MATCH_EQUAL, $status);
		return $this;
	}
	
	public function setDefaultStatus($status){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$state have to be integer");
		}
		
		$this->setCondition(UserPhotoManager::FILTER_DEFAULT_FIELD, Filter::MATCH_EQUAL, $status);
		return $this;
	}
	
	public function setFilename($fileName, $match = Filter::MATCH_EQUAL){
		if(empty($fileName)){
			throw new InvalidIntegerArgumentException("\$fileName is empty");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		
		$this->setCondition(UserPhotoManager::FILTER_FILENAME_FIELD, $match, $fileName);
		return $this;
	}
	
	public function setOrder($field, $order = MysqlDatabase::ORDER_ASC){
		if(empty($field)){
			throw new InvalidArgumentException("\$field have to be non empty string");
		}
		if(empty($order)){
			throw new InvalidArgumentException("\$order have to be non empty string");
		}
		
		parent::setOrder($field, $order);
		return $this;
	}
}
?>