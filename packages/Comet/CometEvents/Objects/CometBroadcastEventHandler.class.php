<?php
abstract class CometBroadcastEventHandler extends CometEventHandler{
	
	public $isBroadcast = true;
	
	abstract public function getUsersListToListenTo();
	abstract public function getEventTypesToListenTo();
}
