<?
class Conversation{
	public $id;
	public $uuid;
	public $user;
	public $interlocutor;
	public $lastMsgDate;
	public $read = 0;
	public $trashed = 0;
	public $fetchFrom = null;
	public $hasAttachment = 0;
}
?>