<?
class LoaderComet extends Loader{
	protected function includes(){
		require_once ('Managers/Comet.class.php');
		require_once ('Objects/CometChunk.class.php');
	}
}
?>