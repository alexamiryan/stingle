<?php
class LoaderSiteNavigation extends Loader{
	protected function includes(){
		stingleInclude ('Exceptions/ControllerTerminateException.class.php');
		stingleInclude ('Exceptions/FileNotFoundException.class.php');
		stingleInclude ('Objects/Nav.class.php');
		stingleInclude ('Managers/RequestParser.class.php');
		stingleInclude ('Managers/Controller.class.php');
		stingleInclude ('Helpers/helpers.php');
	}
	
	protected function loadController(){
		$this->controller = new Controller($this->config->AuxConfig);
		$this->register($this->controller);
	}
	
	protected function loadRequestParser(){
		$this->requestParser = new RequestParser($this->config->AuxConfig);
		$this->register($this->requestParser);
	}
	
	public function hookParse(){
		Reg::register($this->config->ObjectsIgnored->Nav, $this->requestParser->parse());
	}
	
	public function hookExecController(){
		$this->controller->exec();
	}
}
