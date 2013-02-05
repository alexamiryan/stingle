<?php

/**
 * PHP Web Messaging System Object
 *
 * @version 1.3
 * @var $id Message identifier
 * @var $status The status of the message, e.g. NORMAL=0; SAVED=1; DELETED=2
 * @var $sender Login name of the message sender
 * @var $date Message sent date
 * @var $subject Message subject
 * @var $message Message body
 */
class Message
{
	private $id;
	
	public $sender;
	public $receivers;
	public $date;
	public $subject;
	public $message;
	public $read;
	public $parent;

	public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
	}
}


