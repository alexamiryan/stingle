<?php
class ChatUser
{
	public $userId;
	
	public function __construct($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidArgumentException("\$userId has to be non zero integer");
		}
		$this->userId = $userId;
	}
	
	public static function getObject($userId){
		$chatUserClassName = ConfigManager::getConfig("Chat", "Chat")->AuxConfig->chatUserClassName;
		return new $chatUserClassName($userId);
	}
}
