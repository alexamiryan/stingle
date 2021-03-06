<?php
class Conversation{
	public $id;
	public $uuid;
	public $userId;
	public $lastMsgDate;
	public $read = ConversationManager::STATUS_READ_READ;
	public $unreadCount = 0;
	public $trashed = ConversationManager::STATUS_TRASHED_NOT_TRASHED;
	public $hasAttachment = ConversationManager::STATUS_HAS_ATTACHMENT_NO;
	
	public $user;
	
	public $participantIds = array();
	public $participants = array();
}
