<?
class ChatInvitation{
	
	const STATUS_NEW = 0;
	const STATUS_ACCEPTED = 1;
	const STATUS_CANCELED = -1;
	const STATUS_DECLINED = -2;
	
	public $id;
	public $inviterUser;
	public $invitedUser;
	public $invitationMessage;
	public $status;
}
?>