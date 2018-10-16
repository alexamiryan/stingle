<?php
class ConversationMessageProps {
	public $id;
	public $uuid;
	public $messageId;
	public $userId;
	public $read = ConversationManager::STATUS_READ_UNREAD;
	public $deleted = ConversationManager::STATUS_DELETED_NO;
}
