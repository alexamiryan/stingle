<?php
class LoaderApiOutput extends Loader{
	protected function includes(){
		stingleInclude ('Managers/ApiOutput.class.php');
	}
	
	protected function loadApiOutput(){
		$this->register(new ApiOutput());
	}
	
	public function hookApiMainOutput(){
		Reg::get($this->config->Objects->ApiOutput)->output();
	}
}
