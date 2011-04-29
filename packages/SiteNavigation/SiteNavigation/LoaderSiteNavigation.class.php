<?
class LoaderSiteNavigation extends Loader{
	protected function includes(){
		require_once ('Nav.class.php');
		require_once ('RequestParser.class.php');
		require_once ('Controller.class.php');
	}
	
	protected function customInitBeforeObjects(){
		$this->controller = new Controller($this->config->AuxConfig);
	}
	
	protected function loadRequestParser(){
		$this->requestParser = new RequestParser($this->config->AuxConfig);
		Reg::register($this->config->Objects->RequestParser, $this->requestParser);
	}
	
	public function hookParse(){
		Reg::register($this->config->AuxConfig->Nav, $this->requestParser->parse());
	}
	
	public function hookExecController(){
		$this->controller->exec();
	}
}
?>