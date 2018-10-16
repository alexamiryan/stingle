<?php
class ConversationMessage {
	public $id;
	public $uuid;
	public $date;
	public $userId;
	public $message;
	public $hasAttachment = 0;
	public $data = null;
	
	public $props = null;
	public $user = null;
	public $attachments = array();
}
