<?php
class ChatSession{
	
	const CLOSED_REASON_CLOSE = 1; 
	const CLOSED_REASON_OFFLINE = 2; 
	const CLOSED_REASON_MONEY = 3; 
	
	public $id;
	public $chatterUser;
	public $interlocutorUser;
	public $messages = array();
	public $startDate;
	public $closed;
	public $closedBy;
	public $closedReason;
}
?>