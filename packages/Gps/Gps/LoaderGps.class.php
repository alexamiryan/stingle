<?
class LoaderGps extends Loader{
	
	protected function includes(){
		require_once ('Gps.class.php');
	}
	
	protected function loadGps(){
		$this->gps = new Gps();
		Reg::register($this->config->Objects->Gps, $this->gps);
	}
}
?>