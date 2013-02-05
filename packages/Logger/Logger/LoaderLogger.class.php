<?php
class LoaderLogger extends Loader{
	protected function includes(){
		require_once ('Managers/Logger.class.php');
	}
}
