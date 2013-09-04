<?php
class LoaderCometEvents extends Loader{
	protected function includes(){
		stingleInclude ('Filters/CometEventsFilter.class.php');
		stingleInclude ('Managers/CometEvents.class.php');
		stingleInclude ('Objects/CometEventHandler.class.php');
		stingleInclude ('Objects/CometBroadcastEventHandler.class.php');
		stingleInclude ('Objects/CometEventsChunk.class.php');
		stingleInclude ('Objects/CometEvent.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames("CometEvents");
	}
	
	protected function loadCometEvents(){
		$this->register(new CometEvents());
	}
}
