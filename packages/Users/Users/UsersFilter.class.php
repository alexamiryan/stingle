<?
class UsersFilter extends Filter {
	
	public function __construct($only_enabled_users = true){
		if ($only_enabled_users){
			$this->setCondition(UserManagement::FILTER_ENABLED_FIELD, Filter::MATCH_EQUAL, UserManagement::STATE_ENABLE_ENABLED);
		}
	}
	
	public function setUserId($user_id, $match = Filter::MATCH_EQUAL){
		if(empty($user_id)){
			throw new InvalidIntegerArgumentException("\$user_id have to be not empty");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		
		if(!is_array($user_id)){
			if(!is_numeric($user_id)){
				throw new InvalidIntegerArgumentException("\$user_id have to be non zero integer");
			}
			$user_id = array($user_id);
		}
		
		if(count($user_id) == 1){
			$this->setCondition(UserManagement::FILTER_USER_ID_FIELD, $match, $user_id[0]);
		}
		else{
			$this->setCondition(UserManagement::FILTER_USER_ID_FIELD, $match, $user_id);
		}
		return $this;
	}
	
	public function setEnableStatus($status){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$state have to be integer");
		}
		
		$this->setCondition(UserManagement::FILTER_ENABLED_FIELD, Filter::MATCH_EQUAL, $status);
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
	
	public function setOnlineStatus($state){
		if(empty($state)){
			throw new InvalidArgumentException("\$state have to be non empty integer or array");
		}
		
		if(!is_array($state)){
			$state = array($state);
		}
		
		if(count($state)==1){
			$this->setCondition(UserManagement::FILTER_ONLINE_FIELD, Filter::MATCH_EQUAL, $state[0]);
		}
		else{
			$this->setCondition(UserManagement::FILTER_ONLINE_FIELD, Filter::MATCH_IN, $state);
		}
		return $this;
	}
	
	public function setLoginLike($login){
		if(empty($login)){
			throw new InvalidArgumentException("\$login have to be non empty string");
		}
		$this->setCondition(UserManagement::FILTER_LOGIN_FIELD, Filter::MATCH_STARTS, $login);
		return $this;
	}
	
	public function setEmail($email){
		if(empty($email)){
			throw new InvalidArgumentException("\$email have to be non empty string");
		}
		$this->setCondition(UserManagement::FILTER_EMAIL_FIELD, Filter::MATCH_EQUAL, $email);
		return $this;
	}
	
	public function setEmailConfirmationStatus($status = UserManagement::STATE_EMAIL_CONFIRMED){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$status have to be integer");
		}
		$this->setCondition(UserManagement::FILTER_EMAIL_CONFIRMED_FIELD, Filter::MATCH_EQUAL, $status);
		return $this;
	}
	
	public function setGroups($groups, $isGroupNamesGiven = true){
		if(empty($groups)){
			throw new InvalidArgumentException("\$groups have to be non empty string or array");
		}
		if(!is_array($groups)){
			$groups = array($groups);
		}
		
		$this->setExtraJoin(
			UserManagement::TBL_USERS_GROUPS, "ug", "user_id",
			UserManagement::FILTER_USER_ID_FIELD,
			MySqlDatabase::JOIN_LEFT
		);
		
		if($isGroupNamesGiven){
			$this->setExtraJoin(
				UserManagement::TBL_GROUPS, "groups", "id",
				UserManagement::FILTER_GROUP_ID_FIELD,
				MySqlDatabase::JOIN_LEFT
			);
			if(count($groups)==1){
				$this->setCondition(UserManagement::FILTER_GROUP_NAME_FIELD, Filter::MATCH_EQUAL, $groups[0]);
			}
			else{
				$this->setCondition(UserManagement::FILTER_GROUP_NAME_FIELD, Filter::MATCH_IN, $groups);
			}
		}
		else{
			if(count($groups)==1){
				$this->setCondition(UserManagement::FILTER_GROUP_ID_FIELD, Filter::MATCH_EQUAL, $groups[0]);
			}
			else{
				$this->setCondition(UserManagement::FILTER_GROUP_ID_FIELD, Filter::MATCH_IN, $groups);
			}
		}
		return $this;
	}
	
	public function setLastPing($time, $match = Filter::MATCH_GREATER){
		if(empty($time)){
			throw new InvalidArgumentException("\$time have to be non empty string");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		$this->setCondition(UserManagement::FILTER_LAST_PING_FIELD, $match, $time);
		return $this;
	}
	
	public function setCreationDate($time, $match = Filter::MATCH_GREATER){
		if(empty($time)){
			throw new InvalidArgumentException("\$time have to be non empty string");
		}
		if(empty($match)){
			throw new InvalidArgumentException("\$match have to be non empty string");
		}
		$this->setCondition(UserManagement::FILTER_CREATION_DATE_FIELD, $match, $time);
		return $this;
	}
}
?>