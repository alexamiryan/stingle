<?
class LoaderComet extends Loader{
	protected function includes(){
		require_once ('Comet.class.php');
		require_once ('CometChunk.class.php');
	}
}
?>