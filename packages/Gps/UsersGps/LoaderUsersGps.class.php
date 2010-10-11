<?
class LoaderUsersGps extends Loader{
	
	protected function includes(){
		require_once ('UsersGps.class.php');
	}
	
	protected function loadUsersGps(){
		Reg::register($this->config->Objects->UsersGps, new UsersGps());
	}
}
?>