<?php
class LoaderUniversalOutput extends Loader{
	protected function includes(){
		stingleInclude ('Managers/UniversalOutput.class.php');
	}
	
	protected function loadUniversalOutput(){
		$this->register(new UniversalOutput());
	}
	
	public function hookMainOutput(){
		Reg::get($this->config->Objects->UniversalOutput)->output();
	}
}
