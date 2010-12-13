<?
class ChatResponse{
	
	const STATUS_OPEN = 0;
	const STATUS_CLOSED = 1;
	
	private $chatSessionUpdates = array();
	private $invitationsToMe  = array();
	private $myInvitations  = array();
	private $lastId = null;
	private $lastInvitationId = null;
	private $money  = null;
	private $redirect  = null;
	private $closeAllSessions  = false;
	/**
	 *	private $responseArray = array('chats'=>array(),'invitations'=>array(),'money'=>null,'lastId'=>null);
	 */ 
	
	public function response(){
		
		$responseArray = null;
		
		if(!empty($this->chatSessionUpdates)){
			$responseArray['chatSessionUpdates'] = $this->chatSessionUpdates;
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
		
		if($this->redirect !== null){
			$responseArray['redirectTo'] = $this->redirect;
		}
		
		if($this->closeAllSessions !== false){
			$responseArray['closeAllSessions'] = 1;
		}
		
		return $responseArray;
	}
	
	public function addChatSession(ChatSession $chatSession){
		if(!in_array($chatSession, $this->chatSessionUpdates)){
			array_push($this->chatSessionUpdates, $chatSession);
		}
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
	
	public function setRedirect($url){
		$this->redirect = $url;
	}
	
	public function closeAllSessions(){
		$this->closeAllSessions = true;
	}
}
?>