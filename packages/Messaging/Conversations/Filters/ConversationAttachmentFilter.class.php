<?
class ConversationAttachmentFilter extends Filter {
	
	public function __construct($headersOnly = true){
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
}
?>