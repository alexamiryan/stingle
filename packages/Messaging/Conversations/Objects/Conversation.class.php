<?php
class Conversation{
	public $id;
	public $uuid;
	public $userId;
	public $user;
	public $interlocutorId;
	public $interlocutor;
	public $lastMsgDate;
	public $read = ConversationManager::STATUS_READ_READ;
	public $unreadCount = 0;
	public $trashed = ConversationManager::STATUS_TRASHED_NOT_TRAHSED;
	public $fetchFrom = null;
	public $hasAttachment = ConversationManager::STATUS_HAS_ATTACHMENT_NO;
}
