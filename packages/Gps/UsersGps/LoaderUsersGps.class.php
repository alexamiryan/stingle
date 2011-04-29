<?
class LoaderUsersGps extends Loader{
	
	protected function includes(){
		require_once ('UsersGps.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('UsersGps');
	}
	
	protected function loadUsersGps(){
		Reg::register($this->config->Objects->UsersGps, new UsersGps());
	}
}
?>