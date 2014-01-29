<?php
class Conversation{
	public $id;
	public $uuid;
	public $userId;
	public $user;
	public $interlocutorId;
	public $interlocutor;
	public $lastMsgDate;
	public $read = 1;
	public $unreadCount = 0;
	public $trashed = 0;
	public $fetchFrom = null;
	public $hasAttachment = 0;
	public $system = 0;
}
