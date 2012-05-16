<?
class Controller
{
	private $config;
	
	public function __construct(Config $config){
		$this->config = $config;
	}
	
	public function exec(){
		$nav = Reg::get($this->config->ObjectsIgnored->Nav);
		
		$firstLevel = $this->config->AuxConfig->firstLevelName;
		$secondLevel = $this->config->AuxConfig->secondLevelName;
		$action = $this->config->AuxConfig->actionName;
		
		
		if(@file_exists("{$this->config->AuxConfig->modulesDir}/{$nav->$firstLevel}/config.php")){
			include ("{$this->config->AuxConfig->modulesDir}/{$nav->$firstLevel}/config.php");
		}

		if(@file_exists("{$this->config->AuxConfig->modulesDir}/{$nav->$firstLevel}/common.php")){
			include ("{$this->config->AuxConfig->modulesDir}/{$nav->$firstLevel}/common.php");
		}		
		
		if(!empty($nav->$action) and @file_exists("{$this->config->AuxConfig->modulesDir}/{$nav->$firstLevel}/actions/{$nav->$action}.php")){
			include ("{$this->config->AuxConfig->modulesDir}/{$nav->$firstLevel}/actions/{$nav->$action}.php");
		}
		
		if(file_exists("{$this->config->AuxConfig->modulesDir}/{$nav->$firstLevel}/{$nav->$secondLevel}.php")){
			include ("{$this->config->AuxConfig->modulesDir}/{$nav->$firstLevel}/{$nav->$secondLevel}.php");
		}
	}
}
?>