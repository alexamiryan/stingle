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
