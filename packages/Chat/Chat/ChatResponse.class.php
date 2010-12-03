<?
class ChatResponse{
	
	private $chats  = array();
	private $chatInvitations  = array();
	private $lastId = null;
	private $money  = null;
	/**
	 *	private $responseArray = array('chats'=>array(),'invitations'=>array(),'money'=>null,'lastId'=>null);
	 */ 
	
	public function response(){
		
		$responseArray = null;
		
		if(!empty($this->chats)){
			$responseArray['chats'] = $this->chats;
		}
		
		if(!empty($this->chatInvitations)){
			$responseArray['invitations'] = $this->chatInvitations;
		}
		
		if($this->lastId !== null){
			$responseArray['lastId'] = $this->lastId;
		}
		
		if($this->money !== null){
			$responseArray['money'] = $this->money;
		}
		
		return $responseArray;
	}
	
	public function setChats($array){
		$this->chats = $array;
	}
	
	public function setInvitations($array){
		$this->chatInvitations = $array;
	}
	
	public function setLastId($id){
		$this->lastId = $id;
	}
	
	public function setMoney($money){
		$this->money = $money;
	}
}
?>