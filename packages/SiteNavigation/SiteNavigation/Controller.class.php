<?
class Controller
{
	private $config;
	
	public function __construct(Config $config){
		$this->config = $config;
	}
	
	public function exec(){
		$nav = Reg::get($this->config->ObjectsIgnored->Nav);
		$module = $nav->{$this->config->AuxConfig->firstLevelName};
		$page = $nav->{$this->config->AuxConfig->secondLevelName};
		$action = $nav->{$this->config->AuxConfig->actionName};
		
		if(@file_exists("{$this->config->AuxConfig->modulesDir}/$module/config.php")){
			include ("{$this->config->AuxConfig->modulesDir}/$module/config.php");
		}

		if(@file_exists("{$this->config->AuxConfig->modulesDir}/$module/common.php")){
			include ("{$this->config->AuxConfig->modulesDir}/$module/common.php");
		}		
		
		if(!empty($action) and @file_exists("{$this->config->AuxConfig->modulesDir}/$module/actions/$action.php")){
			include ("{$this->config->AuxConfig->modulesDir}/$module/actions/$action.php");
		}
		
		if(file_exists("{$this->config->AuxConfig->modulesDir}/$module/$page.php")){
			include ("{$this->config->AuxConfig->modulesDir}/$module/$page.php");
		}
	}
}
?>