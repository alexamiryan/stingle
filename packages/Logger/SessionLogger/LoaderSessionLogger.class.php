<?php
class LoaderSessionLogger extends Loader{
	protected function includes(){
		stingleInclude ('Managers/SessionLogger.class.php');
	}
}
