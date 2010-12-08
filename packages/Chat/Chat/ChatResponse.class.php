<?
class ChatResponse{
	
	const STATUS_OPEN = 0;
	const STATUS_CLOSED = 1;
	
	private $chats  = array();
	private $invitationsToMe  = array();
	private $myInvitations  = array();
	private $lastId = null;
	private $lastInvitationId = null;
	private $money  = null;
	private $sessionUpdates = array();
	/**
	 *	private $responseArray = array('chats'=>array(),'invitations'=>array(),'money'=>null,'lastId'=>null);
	 */ 
	
	public function response(){
		
		$responseArray = null;
		
		if(!empty($this->chats)){
			$responseArray['chats'] = $this->chats;
		}
		
		if(!empty($this->invitationsToMe)){
			$responseArray['invitationsToMe'] = $this->invitationsToMe;
		}
		
		if(!empty($this->myInvitations)){
			$responseArray['myInvitations'] = $this->myInvitations;
		}
		
		if(!empty($this->sessionUpdates)){
			$responseArray['sessionUpdates'] = $this->sessionUpdates;
		}
		
		if($this->lastId !== null){
			$responseArray['lastId'] = $this->lastId;
		}
		
		if($this->lastInvitationId !== null){
			$responseArray['lastInvId'] = $this->lastInvitationId;
		}
		
		if($this->money !== null){
			$responseArray['money'] = $this->money;
		}
		
		return $responseArray;
	}
	
	public function setChats($array){
		$this->chats = $array;
	}
	
	public function setInvitationsToMe($array){
		$this->invitationsToMe = $array;
	}
	
	public function setMyInvitations($array){
		$this->myInvitations = $array;
	}
	
	public function setLastId($id){
		$this->lastId = $id;
	}
	
	public function setLastInvitationId($id){
		$this->lastInvitationId = $id;
	}
	
	public function setMoney($money){
		$this->money = $money;
	}
	
	public function addUpdatedSession(ChatSession $session){
		array_push($this->sessionUpdates, $session);
	}
}
?>