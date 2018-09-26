<?php
class LoaderUniversalOutput extends Loader{
	protected function includes(){
		stingleInclude ('Managers/UniversalOutput.class.php');
	}
	
	protected function loadUniversalOutput(){
		$this->register(new UniversalOutput());
	}
	
	public function hookSetRequestType(){
		if(isset($_GET['ajax']) && $_GET['ajax'] == 1){
			Reg::get($this->config->Objects->UniversalOutput)->setJSONOutput();
		}
	}
	
	public function hookMainOutput(){
		Reg::get($this->config->Objects->UniversalOutput)->output();
	}
}
