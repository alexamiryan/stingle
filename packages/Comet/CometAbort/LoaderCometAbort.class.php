<?php
class LoaderCometAbort extends Loader{
	protected function includes(){
		stingleInclude ('Managers/CometAbort.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('CometAbort');
	}
}
