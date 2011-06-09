<?
class LoaderOneTimeCodes extends Loader{
	protected function includes(){
		require_once ('OneTimeCodes.class.php');
		require_once ('OTCConfig.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('OneTimeCodes');
	}
	
	protected function loadOneTimeCodes(){
		Reg::register($this->config->Objects->OneTimeCodes, new OneTimeCodes($this->config->AuxConfig));
	}
}
?>