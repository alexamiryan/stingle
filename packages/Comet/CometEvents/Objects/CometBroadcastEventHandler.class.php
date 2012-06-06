<?
abstract class CometBroadcastEventHandler extends CometEventHandler{
	
	public $isBroadcast = true;
	
	abstract public function getUsersListToListenTo();
}
?>