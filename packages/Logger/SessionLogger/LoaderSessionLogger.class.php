<?php
class LoaderSessionLogger extends Loader{
	protected function includes(){
		require_once ('Managers/SessionLogger.class.php');
	}
}
