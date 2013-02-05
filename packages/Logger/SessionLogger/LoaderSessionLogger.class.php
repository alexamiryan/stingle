<?php
class LoaderSessionLogger extends Loader{
	protected function includes(){
		require_once ('SessionLogger.class.php');
	}
}
