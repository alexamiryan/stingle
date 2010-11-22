<?
class LoaderChat extends Loader{
	
	protected function includes(){
		require_once ('ChatMessage.class.php');
		require_once ('ChatMessageFilter.class.php');
		require_once ('Chat.class.php');		
		require_once ('ChatInvitation.class.php');
		require_once ('ChatResponse.class.php');
		
		if($this->config->ManagerAlgorithm == 'byLastId'){
			require_once ('ChatManagerByLastId.class.php');
		}
		elseif ($this->config->ManagerAlgorithm == 'byMakeRead'){
			require_once ('ChatManagerByMakeRead.class.php');
		}
	}
	
	protected function customInitBeforeObjects(){
		if($this->config->ManagerAlgorithm == 'byLastId'){
			Tbl::registerTableNames('ChatManagerByLastId');
		}
		elseif ($this->config->ManagerAlgorithm == 'byMakeRead'){
			Tbl::registerTableNames('ChatManagerByMakeRead');
		}
	}
	
	protected function loadChatManager(){
		if($this->config->ManagerAlgorithm == 'byLastId'){
			Reg::register($this->config->Objects->ChatManager, new ChatManagerByLastId());
		}
		elseif ($this->config->ManagerAlgorithm == 'byMakeRead'){
			Reg::register($this->config->Objects->ChatManager, new ChatManagerByMakeRead());
		}
	}
}
?>