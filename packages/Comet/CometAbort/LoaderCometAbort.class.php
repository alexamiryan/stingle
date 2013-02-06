<?php
class LoaderCometAbort extends Loader{
	protected function includes(){
		require_once ('Managers/CometAbort.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('CometAbort');
	}
}
