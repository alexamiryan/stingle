<?php
class LoaderUserSessions extends Loader{
	protected function includes(){
		stingleInclude ('Filters/UserSessionFilter.class.php');
		stingleInclude ('Objects/UserSession.class.php');
		stingleInclude ('Managers/UserSessionsManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('UserSessionsManager');
	}
	
	protected function loadUserSessions(){
		$this->register(new UserSessionsManager($this->config));
	}
	
}
