<?php
class LoaderGps extends Loader{
	
	protected function includes(){
		stingleInclude ('Managers/Gps.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('Gps');
	}
	
	protected function loadGps(){
		$this->gps = new Gps();
		$this->register($this->gps);
	}
}
