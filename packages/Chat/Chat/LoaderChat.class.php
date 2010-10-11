<?
class LoaderChat extends Loader{
	
	protected function includes(){
		require_once ('Chat.class.php');
	}
	
	protected function loadChat(){
		Reg::register($this->config->Objects->Chat, new Chat($this->config));
	}
}
?>