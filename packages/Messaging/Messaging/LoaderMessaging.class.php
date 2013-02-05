<?php
class LoaderMessaging extends Loader{
	protected function includes(){
		require_once ('Managers/MessageManagement.class.php');
		require_once ('Objects/Message.class.php');
		require_once ('Filters/MessageFilter.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('MessageManagement');
	}
	
	protected function loadMessageManagement(){
		$this->register(new MessageManagement());
	}
}
