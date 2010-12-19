<?
class ChatResponse{
	
	private $chatSessionUpdates = array();
	private $invitationsToMe  = array();
	private $myInvitations  = array();
	private $lastId = null;
	private $lastInvitationId = null;
	private $money  = null;
	private $redirect  = null;
	private $closeAllSessions  = false;
	private $messages  = array();
	private $openSessions  = null;
	private $openMyInvitations  = null;
	private $openInvitationsToMe  = null;
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
			$responseArray['closeAllSessions'] = $this->closeAllSessions;
		}
		
		if(!empty($this->messages)){
			$responseArray['messages'] = $this->messages;
		}
		
		if($this->openSessions !== null){
			$responseArray['openSessions'] = $this->openSessions;
		}
		
		if($this->openMyInvitations  !== null){
			$responseArray['openMyInvitations'] = $this->openMyInvitations;
		}
		
		if($this->openInvitationsToMe  !== null){
			$responseArray['openInvitationsToMe'] = $this->openInvitationsToMe;
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
	
	public function closeAllSessions($reason = 0){
		$this->closeAllSessions = $reason;
	}
	
	public function addMessage($message){
		if(!in_array($message, $this->messages)){
			array_push($this->messages, $message);
		}
	}
	
	public function setOpenSessions($array){
		$this->openSessions = $array;
	}
	
	public function setOpenMyInvitations($array){
		$this->openMyInvitations = $array;
	}
	
	public function setOpenInvitationsToMe($array){
		$this->openInvitationsToMe = $array;
	}
}
?>