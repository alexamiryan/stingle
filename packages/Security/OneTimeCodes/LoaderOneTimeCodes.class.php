<?php
class LoaderOneTimeCodes extends Loader{
	protected function includes(){
		stingleInclude ('Managers/OneTimeCodes.class.php');
		stingleInclude ('Objects/OTCConfig.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('OneTimeCodes');
	}
	
	protected function loadOneTimeCodes(){
		$this->register(new OneTimeCodes($this->config->AuxConfig));
	}
}
