<?
class LoaderCometEvents extends Loader{
	protected function includes(){
		require_once ('Managers/CometEvents.class.php');
		require_once ('Objects/CometEventsChunk.class.php');
		require_once ('Objects/CometEvent.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames("CometEvents");
	}
	
	protected function loadCometEvents(){
		$this->register(new CometEvents());
	}
}
?>