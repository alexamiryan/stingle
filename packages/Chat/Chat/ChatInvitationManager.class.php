<?php
class ChatInvitationManager extends Filterable
{
	const TBL_CHAT_INVITATIONS = 'chat_invitations';
	
	const STATUS_NEW = 0;
	const STATUS_ACCEPTED = 1;
	const STATUS_CANCELED = -1;
	const STATUS_DECLINED = -2;
	
	const FILTER_ID_FIELD = 'id';
	const FILTER_SENDER_USER_ID_FIELD = 'sender_user_id';
	const FILTER_RECEIVER_USER_ID_FIELD = 'receiver_user_id';
	const FILTER_DATE_FIELD = 'date';
	const FILTER_STATUS_FIELD = 'status';
	
	private $invitationClearTimeout = 5;  // in minutes
	
	public function __construct(Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		
		if(isset($config->invitationClearTimeout)){
			$this->invitationClearTimeout = $config->invitationClearTimeout;
		}
	}
	
	protected function getFilterableFieldAlias($field){
		switch($field){
			case static::FILTER_ID_FIELD :
			case static::FILTER_SENDER_USER_ID_FIELD :
			case static::FILTER_RECEIVER_USER_ID_FIELD :
			case static::FILTER_DATE_FIELD :
			case static::FILTER_STATUS_FIELD :
				return "inv";
		}

		throw new RuntimeException("Specified field does not exist or not filterable");
	}
	
	public function getInvitations(ChatInvitationsFilter $filter){
		$invitationsObjects = array();
		
		$invitationRows = $this->query->exec("SELECT `inv`.*
											FROM `".Tbl::get('TBL_CHAT_INVITATIONS')."` `inv`
											{$this->generateJoins($filter)}
											WHERE 1
											{$this->generateWhere($filter)}
											{$this->generateOrder($filter)}
											{$this->generateLimits($filter)}"
											)->fetchRecords();
										
		foreach ($invitationRows as $invitationRow){
			array_push($invitationsObjects, $this->getInvitationObject($invitationRow));
		}
		return $invitationsObjects;
	}
	
	public function getInvitation(ChatInvitationsFilter $filter){
		$invitations = $this->getInvitations($filter);
		if(count($invitations) !== 1){
			throw new RuntimeException("There is no such invitation or invitation is not unique.");
		}
		return $invitations[0];
	}
	
	protected function getInvitationObject($invitationRow){
		if(empty($invitationRow) or !is_array($invitationRow)){
			throw new InvalidArgumentException("Invalid \$invitationRow specified!");
		}
		
		$invitation = new ChatInvitation();
		
		$invitation->id = $invitationRow['id'];
		$invitation->inviterUser = ChatUser::getObject($invitationRow['sender_user_id']);
		$invitation->invitedUser = ChatUser::getObject($invitationRow['receiver_user_id']);
		$invitation->invitationMessage = $invitationRow['invitation_message'];
		$invitation->status = $invitationRow['status'];

		return $invitation;
	}
	
	public function insertInvitation(ChatInvitation $invitation){
		if(empty($invitation->inviterUser->userId) or !is_numeric($invitation->inviterUser->userId)){
			throw new InvalidArgumentException("Invalid inviterUser specified!");
		}
		if(empty($invitation->invitedUser->userId) or !is_numeric($invitation->invitedUser->userId)){
			throw new InvalidArgumentException("Invalid invitedUser specified!");
		}
		
		$this->query->exec("	INSERT INTO `".Tbl::get('TBL_CHAT_INVITATIONS')."`
										(
											`sender_user_id`, 
											`receiver_user_id`, 
											`invitation_message`,
											`status`)
								VALUES	(
											'{$invitation->inviterUser->userId}', 
											'{$invitation->invitedUser->userId}', 
											'{$invitation->invitationMessage}',
											'{$invitation->status}'
										)");
		
		return $this->query->getLastInsertId();
	}
	
	public function deleteInvitation(ChatInvitation $invitation){
		if(empty($invitation->id) or !is_numeric($invitation->id)){
			throw new InvalidArgumentException("Invalid invitation ID specified!");
		}
		
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_CHAT_INVITATIONS')."` WHERE `id`='{$invitation->id}'");
	}
	
	public function updateInvitationStatus($inviterUserId, $invitedUserId, $newStatus){
		$filter = new ChatInvitationsFilter();
		$filter->setSenderUserId($inviterUserId);
		$filter->setReceiverUserId($invitedUserId);
		$invitation = $this->getInvitation($filter);
		
		$this->deleteInvitation($invitation);
		
		$invitation->status = $newStatus;
		$this->insertInvitation($invitation);
	}
	
	public function clearTimedOutInvitations(){
		$this->query->exec("DELETE FROM `".Tbl::get('TBL_CHAT_INVITATIONS')."` 
								WHERE 	`status` <> 0 AND
										(now() - `date`) >= ".$this->invitationClearTimeout * 60);
		return $this->query->affected();
	}
	
	public function getLastInvitationId(){
		$lastId = $this->query->exec("SELECT MAX(id) as `lastId` FROM `".Tbl::get('TBL_CHAT_INVITATIONS')."`")->fetchField('lastId');
		return (empty($lastId) ? 0 : $lastId);
	}
	
}
?>