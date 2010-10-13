<?
class LoaderChat extends Loader{
	
	protected function includes(){
		require_once ('ChatMessage.class.php');
		require_once ('Chat.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('Chat');
	}
	
	protected function loadChat(){
		Reg::register($this->config->Objects->Chat, new Chat());
	}
}
?>