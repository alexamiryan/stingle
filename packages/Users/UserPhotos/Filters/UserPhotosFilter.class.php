<?php
class UserPhotosFilter extends Filter {
	
	public function __construct(){
		parent::__construct();
	
		$this->qb->select(new Field("*", "up"))
			->from(Tbl::get('TBL_USERS_PHOTOS', 'UserPhotoManager'), "up");
	}
	
	public function setPhotoId($photo_id){
		if(empty($photo_id) or !is_numeric($photo_id)){
			throw new InvalidIntegerArgumentException("\$photo_id have to be non zero integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", "up"), $photo_id));
		return $this;
	}
	
	public function setUserId($user_id){
		if(empty($user_id) or !is_numeric($user_id)){
			throw new InvalidIntegerArgumentException("\$user_id have to be non zero integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("user_id", "up"), $user_id));
		return $this;
	}
	
	public function setStatusEqual($status){
		if(!in_array($status, UserPhotoManager::getConstsArray("MODERATION_STATUS"))){
			throw new InvalidIntegerArgumentException("Invalid \$status given");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("status", "up"), $status));
		return $this;
	}
	
	public function setStatusNotEqual($status){
		if(!in_array($status, UserPhotoManager::getConstsArray("MODERATION_STATUS"))){
			throw new InvalidIntegerArgumentException("Invalid \$status given");
		}
		
		$this->qb->andWhere($this->qb->expr()->notEqual(new Field("status", "up"), $status));
		return $this;
	}
	
	public function setDefaultStatus($status){
		if(!is_numeric($status)){
			throw new InvalidIntegerArgumentException("\$state have to be integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("default", "up"), $status));
		return $this;
	}
	
	public function setFilename($fileName){
		if(empty($fileName) or !is_string($fileName)){
			throw new InvalidIntegerArgumentException("\$fileName have to be non empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("filename", "up"), $fileName));
		return $this;
	}
	
	public function setOrderDefaultAsc(){
		$this->setOrder(new Field("default", "up"), MySqlDatabase::ORDER_ASC);
	}
	
	public function setOrderDefaultDesc(){
		$this->setOrder(new Field("default", "up"), MySqlDatabase::ORDER_DESC);
	}
	
	public function setOrderIdAsc(){
		$this->setOrder(new Field("id", "up"), MySqlDatabase::ORDER_ASC);
	}
	
	public function setOrderIdDesc(){
		$this->setOrder(new Field("id", "up"), MySqlDatabase::ORDER_DESC);
	}
}
