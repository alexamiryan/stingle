<?php
class LoaderMessaging extends Loader{
	protected function includes(){
		stingleInclude ('Managers/MessageManagement.class.php');
		stingleInclude ('Objects/Message.class.php');
		stingleInclude ('Filters/MessageFilter.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('MessageManagement');
	}
	
	protected function loadMessageManagement(){
		$this->register(new MessageManagement());
	}
}
