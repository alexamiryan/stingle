<?
class ChatComet extends CometChunk{
	
	protected $chatSessionUpdates = array();
	protected $invitationsToMe  = array();
	protected $myInvitations  = array();
	protected $lastId = null;
	protected $lastInvId = null;
	protected $money  = null;
	protected $closeAllSessions  = false;
	protected $openSessions  = null;
	protected $openMyInvitations  = null;
	protected $openInvitationsToMe  = null;
	
	protected $redirect  = null;
	protected $messages  = array();
	
	public function __construct($params){
		$this->setName('chat');
		
		$this->lastId = $params['lastId'];
		$this->lastInvId = $params['lastInvId'];
	}
	
	protected function setRedirect($url){
		$this->setIsAnyData();
		$this->redirect = $url;
	}
	
	
	protected function addMessage($message){
		$this->setIsAnyData();
		if(!in_array($message, $this->messages)){
			array_push($this->messages, $message);
		}
	}
	
	protected function addChatSession(ChatSession $chatSession){
		$this->setIsAnyData();
		if(!in_array($chatSession, $this->chatSessionUpdates)){
			array_push($this->chatSessionUpdates, $chatSession);
		}
	}
	
	protected function setInvitationsToMe($array){
		if(!empty($array)){
			$this->setIsAnyData();
		}
		$this->invitationsToMe = $array;
	}
	
	protected function setMyInvitations($array){
		if(!empty($array)){
			$this->setIsAnyData();
		}
		$this->myInvitations = $array;
	}
	
	protected function setMoney($money){
		$this->setIsAnyData();
		$this->money = $money;
	}
	
	protected function closeAllSessions($reason = 0, $redirectUrl = null){
		$this->setIsAnyData();
		$this->closeAllSessions = array("reason"=>$reason, 'redirectUrl' => $redirectUrl);
	}
	
	
	public function run(){
		$runInterval = ConfigManager::getConfig("Comet", "Comet")->AuxConfig->runInterval;
		
		// New Invitations by Last Id
		$invitationFilter = new ChatInvitationsFilter();
		$invitationFilter->setEitherUserId(Reg::get('usr')->getId());
		$invitationFilter->setIdGreater($this->lastInvId);
		$invitations = Reg::get('chatInvMgr')->getInvitations($invitationFilter);
	
		$invitationsToMe = array();
		$myInvitations = array();
	
		$needToWait = false;
		foreach($invitations as $invitation){
			if($invitation->inviterUser->userId == Reg::get('usr')->getId()){
				if($invitation->status == ChatInvitationManager::STATUS_DECLINED){
					$this->addMessage(sprintf(CHAT_INVITATION_REJECT, $invitation->invitedUser->userName));
				}
				array_push($myInvitations, $invitation);
			}
			else{
				array_push($invitationsToMe, $invitation);
			}
			if($invitation->status != ChatInvitationManager::STATUS_NEW){
				$needToWait = true;
			}
		}
	
		if($needToWait){
			// Wait for defined inteval, because maybe some other tabs(threads) need
			// to grab same info from db before our thread will delete it
			usleep($runInterval * 1000000);
		}
	
		$this->setMyInvitations($myInvitations);
		$this->setInvitationsToMe($invitationsToMe);
	
		// Open Invitations for UI Sync
		$openInvitationFilter = new ChatInvitationsFilter();
		$openInvitationFilter->setEitherUserId(Reg::get('usr')->getId());
		$openInvitationFilter->setInvitationStatus(ChatInvitationManager::STATUS_NEW);
		$openInvitations = Reg::get('chatInvMgr')->getInvitations($openInvitationFilter);
	
		$this->openMyInvitations = array();
		$this->openInvitationsToMe = array();
		foreach($openInvitations as $invitation){
			if($invitation->inviterUser->userId == Reg::get('usr')->getId()){
				array_push($this->openMyInvitations, $invitation);
			}
			else{
				array_push($this->openInvitationsToMe, $invitation);
			}
		}
	
		// Chat Sessions
	
		$sessionFilter = new ChatSessionFilter();
		$sessionFilter->setEitherUserId(Reg::get('usr')->getId());
		$chatSessions = Reg::get('chatSessMgr')->getChatSessions($sessionFilter, Reg::get('usr')->getId());
	
		$this->openSessions = array();
		$interlocutorIds = ChatSessionManager::getInterlocutorsFromSessions($chatSessions);
		if(!empty($interlocutorIds)){
			$messageFilter = new ChatMessageFilter();
			$messageFilter->setAllMessagesWithInterlocutors(Reg::get('usr')->getId(), $interlocutorIds);
			$messageFilter->setOrderDatetimeAsc();
			$messageFilter->setMessageIdGreater($this->lastId);
				
			$chatMessages = Reg::get('chatMsgMgr')->getChatMessages($messageFilter);
			Reg::get('chatMsgMgr')->parseMessagesForSmilies($chatMessages);
			ChatSessionManager::fillSessionsWithMessages($chatSessions, $chatMessages);
				
			foreach($chatSessions as $chatSession){
	
				// Init variables
				$interlocutorId = $chatSession->interlocutorUser->userId;
				$interlocutorExtra = Reg::get('um')->getUserExtra($interlocutorId, array('online'));
				$interlocutorOnlineStatus = $interlocutorExtra['online'];
	
				// Check if interlocutor is offline
				if($chatSession->closed == ChatSessionManager::CLOSED_STATUS_NO and $interlocutorOnlineStatus == DatingClubUserManagement::STATE_ONLINE_OFFLINE){
					Reg::get('chatSessMgr')->closeSession($chatSession, $chatSession->interlocutorUser, ChatSessionManager::CLOSED_REASON_OFFLINE);
					$this->addChatSession($chatSession);
					$this->addMessage(sprintf(CHAT_INTER_GONE_OFFLINE, $chatSession->interlocutorUser->userName));
				}
	
				// Check maybe interlocuter closed the chat
				if($chatSession->closed == ChatSessionManager::CLOSED_STATUS_YES){
					// Chat closed not by me
					if($chatSession->closedBy != Reg::get('usr')->getId()){
						if($chatSession->closedReason == ChatSessionManager::CLOSED_REASON_CLOSE){
							$this->addMessage(sprintf(CHAT_INTER_CLOSED_CHAT, $chatSession->interlocutorUser->userName));
						}
						elseif($chatSession->closedReason == ChatSessionManager::CLOSED_REASON_MONEY){
							$this->addMessage(sprintf(CHAT_CLOSED_INTER_HAVE_NO_MONEY, $chatSession->interlocutorUser->userName));
						}
						elseif($chatSession->closedReason == ChatSessionManager::CLOSED_REASON_SYNC_UI){
							$this->addMessage(sprintf(CHAT_CLOSED_UNEXPECTED_ERROR, $chatSession->interlocutorUser->userName));
						}
					}
					$this->addChatSession($chatSession);
					// Wait for defined inteval, because maybe some other tabs(threads) need
					// to grab same info from db before our thread will delete it
					usleep($runInterval * 1000000);
				}
	
				// Check for new messages
				if(count($chatSession->messages)){
					$this->addChatSession($chatSession);
				}
	
				// Payment part
				// Do not handle payment for closed sessions
				if($chatSession->closed == ChatSessionManager::CLOSED_STATUS_NO){
					if(ChatPayment::isTimeToPay($chatSession->chatterUser, $chatSession->interlocutorUser)){
						session_start();
						if(ChatPayment::isTimeToPay($chatSession->chatterUser, $chatSession->interlocutorUser)){
							if(pay($interlocutorId, DatingClubPayment::ACTION_CHAT, null, false)){
								ChatPayment::madePayment($chatSession->chatterUser, $chatSession->interlocutorUser);
								$this->setMoney(Reg::get('usr')->money);
							}
							else{
								// If user have no money close all sessions and give appropriate message.
								Reg::get('chatSessMgr')->closeSession($chatSession, $chatSession->chatterUser, ChatSessionManager::CLOSED_REASON_MONEY);
								$this->addChatSession($chatSession);
								$this->addMessage(sprintf(CHAT_I_HAVE_NO_MONEY, SITE_PATH . 'recharge'));
							}
						}
						session_write_close();
					}
				}
	
				// Open Sessions for UI Sync
				if($chatSession->closed == ChatSessionManager::CLOSED_STATUS_NO){
					array_push($this->openSessions, $chatSession->interlocutorUser->userId);
				}
			}
		}
	}
	
	public function getDataArray(){
		
		$DbLastInvId = Reg::get('chatInvMgr')->getLastInvitationId();
		if($DbLastInvId > $this->lastInvId){
			$this->lastInvId = $DbLastInvId;
		}
			
		// Messages Last Id
		$DbLastId = Reg::get('chatMsgMgr')->getLastId();
		if($DbLastId > $this->lastId){
			$this->lastId = $DbLastId;
		}
			
		$responseArray = array();
		
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
		
		if($this->lastInvId !== null){
			$responseArray['lastInvId'] = $this->lastInvId;
		}
		
		if($this->money !== null){
			$responseArray['money'] = $this->money;
		}
		
		if($this->closeAllSessions !== false){
			$responseArray['closeAllSessions'] = $this->closeAllSessions;
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
		
		if($this->redirect !== null){
			$responseArray['redirect'] = $this->redirect;
		}
		
		if(!empty($this->messages)){
			$responseArray['messages'] = $this->messages;
		}
		
		return $responseArray;
	}
}
?>