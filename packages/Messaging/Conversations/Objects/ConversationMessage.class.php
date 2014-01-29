<?php
class ConversationMessage {
	public $id;
	public $uuid;
	public $date;
	public $senderId;
	public $sender;
	public $receiverId;
	public $receiver;
	public $message;
	public $read;
	public $deleted;
	public $hasAttachment = 0;
	public $attachments = array();
	public $system;
}
