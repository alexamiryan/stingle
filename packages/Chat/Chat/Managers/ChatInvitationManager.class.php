<?php
class ChatInvitationManager extends DbAccessor
{
	const TBL_CHAT_INVITATIONS = 'chat_invitations';
	
	const STATUS_NEW = 0;
	const STATUS_ACCEPTED = 1;
	const STATUS_CANCELED = -1;
	const STATUS_DECLINED = -2;
	
	private $invitationClearTimeout = 5;  // in minutes
	
	public function __construct(Config $config, $instanceName = null){
		parent::__construct($instanceName);
		
		if(isset($config->invitationClearTimeout)){
			$this->invitationClearTimeout = $config->invitationClearTimeout;
		}
	}
	
	public function getInvitations(ChatInvitationsFilter $filter){
		$invitationsObjects = array();
		
		if($filter == null){
			$filter = new ChatInvitationsFilter();
		}
		
		$sqlQuery = $filter->getSQL();
		
		//echo $sqlQuery;
		
		$invitationRows = $this->query->exec($sqlQuery)->fetchRecords();
										
		foreach ($invitationRows as $invitationRow){
			array_push($invitationsObjects, $this->getInvitationObject($invitationRow));
		}
		return $invitationsObjects;
	}
	
	public function getInvitation(ChatInvitationsFilter $filter){
		$invitations = $this->getInvitations($filter);
		if(count($invitations) !== 1){
			throw new ChatInvitationException("There is no such invitation or invitation is not unique.");
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
		$qb = new QueryBuilder();
		$qb->insert(Tbl::get('TBL_CHAT_INVITATIONS'))
			->values(array(
							"sender_user_id" => $invitation->inviterUser->userId, 
							"receiver_user_id" => $invitation->invitedUser->userId,
							"invitation_message" => $invitation->invitationMessage,
							"status" => $invitation->status
						)
					);
		$this->query->exec($qb->getSQL());			
		
		return $this->query->getLastInsertId();
	}
	
	public function deleteInvitation(ChatInvitation $invitation){
		if(empty($invitation->id) or !is_numeric($invitation->id)){
			throw new InvalidArgumentException("Invalid invitation ID specified!");
		}
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_CHAT_INVITATIONS'))
			->where($qb->expr()->equal(new Field("id"), $invitation->id));
		$this->query->exec($qb->getSQL());
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
		$qb = new QueryBuilder();
		$qb->delete(Tbl::get('TBL_CHAT_INVITATIONS'))
			->where($qb->expr()->greaterEqual(	$qb->expr()->diff(new Func('NOW'),  new Field('date')), 
												$qb->expr()->prod($this->invitationClearTimeout, 60)));
		$this->query->exec($qb->getSQL());
		return $this->query->affected();
	}
	
	public function getLastInvitationId(){
		$qb = new QueryBuilder();
		$qb->select($qb->expr()->max(new Field('id'), 'lastId'))
			->from(Tbl::get('TBL_CHAT_INVITATIONS'));
		
		$lastId = $this->query->exec($qb->getSQL())->fetchField('lastId');
		return (empty($lastId) ? 0 : $lastId);
	}
}
