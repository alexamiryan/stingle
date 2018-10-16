<?php
class ConversationAttachmentFilter extends Filter {
	
	public function __construct(){
		parent::__construct();
		
		$this->qb->select(new Field("*"))
			->from(Tbl::get('TBL_CONVERSATION_ATTACHEMENTS', 'ConversationAttachmentManager'), "attach");
	}
	
	public function setId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", "attach"), $id));
		return $this;
	}
	
	public function setUUID($uuid){
		if(empty($uuid) or !is_numeric($uuid)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("uuid", "attach"), $uuid));
		return $this;
	}
	
	public function setMessageId($messageId){
		if(empty($messageId) or !is_numeric($messageId)){
			throw new InvalidIntegerArgumentException("\$messageId have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("message_id", "attach"), $messageId));
		return $this;
	}
	
	public function setSenderId($senderId){
		if(empty($senderId) or !is_numeric($senderId)){
			throw new InvalidIntegerArgumentException("\$senderId have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("sender_id", "attach"), $senderId));
		return $this;
	}
	
	public function setReceiverId($receiverId){
		if(empty($receiverId) or !is_numeric($receiverId)){
			throw new InvalidIntegerArgumentException("\$receiverId have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("receiver_id", "attach"), $receiverId));
		return $this;
	}
	
	public function setFlag($flag){
		if(!is_numeric($flag)){
			throw new InvalidIntegerArgumentException("\$flag have to be integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("flag", "attach"), $flag));
		return $this;
	}
	
	public function setFlagIn($flags){
		if(!is_array($flags)){
			throw new InvalidIntegerArgumentException("\$flags have to be array.");
		}
	
		$this->qb->andWhere($this->qb->expr()->in(new Field("flag", "attach"), $flags));
		return $this;
	}
	
	public function setFilename($filename){
		if(empty($filename)){
			throw new InvalidIntegerArgumentException("\$uuid have to be non empty string.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("filename", "attach"), $filename));
		return $this;
	}
	
	public function setIdAsc() {
		$this->setOrder(new Field('id', 'attach'), MySqlDatabase::ORDER_ASC);
	}

	public function setIdDesc() {
		$this->setOrder(new Field('id', 'attach'), MySqlDatabase::ORDER_DESC);
	}

	public function setDateAsc() {
		$this->setOrder(new Field('date', 'attach'), MySqlDatabase::ORDER_ASC);
	}

	public function setDateDesc() {
		$this->setOrder(new Field('date', 'attach'), MySqlDatabase::ORDER_DESC);
	}
}
