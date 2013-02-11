<?php
class LoaderSiteNavigation extends Loader{
	protected function includes(){
		require_once ('Exceptions/ControllerTerminateException.class.php');
		require_once ('Exceptions/FileNotFoundException.class.php');
		require_once ('Objects/Nav.class.php');
		require_once ('Managers/RequestParser.class.php');
		require_once ('Managers/Controller.class.php');
		require_once ('Helpers/helpers.php');
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
