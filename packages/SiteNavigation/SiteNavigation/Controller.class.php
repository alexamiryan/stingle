<?
class Controller
{
	private $config;
	
	public function __construct(Config $config){
		$this->config = $config;
	}
	
	public function exec(){
		$nav = Reg::get($this->config->Nav);
		$module = $nav->{$this->config->firstLevelName};
		$page = $nav->{$this->config->secondLevelName};
		$action = $nav->{$this->config->actionName};

		if(@file_exists("{$this->config->modulesDir}/$module/common.php")){
			include ("{$this->config->modulesDir}/$module/common.php");
		}
		
		if(@file_exists("{$this->config->modulesDir}/$module/config.php")){
			include ("{$this->config->modulesDir}/$module/config.php");
		}
		
		if(!empty($action) and @file_exists("{$this->config->modulesDir}/$module/actions/$action.php")){
			include ("{$this->config->modulesDir}/$module/actions/$action.php");
		}
		
		if(file_exists("{$this->config->modulesDir}/$module/$page.php")){
			include ("{$this->config->modulesDir}/$module/$page.php");
		}
	}
}
?>