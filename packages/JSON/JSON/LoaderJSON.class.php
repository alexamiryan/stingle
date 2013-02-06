<?php
class LoaderJSON extends Loader{
	protected function includes(){
		require_once ('Managers/JSON.class.php');
	}
}
