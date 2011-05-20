<?
class LoaderMessaging extends Loader{
	protected function includes(){
		require_once ('MessageManagement.class.php');
		require_once ('Message.class.php');
		require_once ('MessageFilter.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('MessageManagement');
	}
	
	protected function loadMessageManagement(){
		$messageManagement = new MessageManagement();
		Reg::register($this->config->Objects->MessageManagement, $messageManagement);
	}
}
?>