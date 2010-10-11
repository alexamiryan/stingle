<?
class LoaderIpFilter extends Loader{
	protected function includes(){
		require_once ('IPBlockedException.class.php');
		require_once ('IpFilter.class.php');
	}
	
	protected function loadIpFilter(){
		Reg::register($this->config->Objects->ipFilter, new IpFilter());
	}
}
?>