<?
class RequestParser
{
	private $config;
	
	public function __construct(Config $config){
		$this->config = $config;
	}
	
	public function parse(){
		$nav = new Nav();
		$levels = ConfigManager::getConfig("RewriteURL", "RewriteURL")->AuxConfig->levels->toArray();
		
		// Count how much levels exists in $_GET
		$existentLevelsCount = 0;
		foreach($levels as $level){
			// If level exists in $_GET
			if(isset($_GET[$level]) and !empty($_GET[$level])){
				// Validate level
				if(preg_match($this->config->validationRegExp, $_GET[$level])){
					if($_GET[$level] == 'actions'){
						throw new RuntimeException("You can't have level with name actions!");
					}
					// This one is ok
					$nav->$level = $_GET[$level];
					$existentLevelsCount++;
				}
				else{
					// If regexp didn't passed we just discard it and stop
					$_GET[$level] = null;
					break;
				}
			}
			else{
				break;
			}
		}
		
		// If no levels specified in $_GET we just take default first level value 
		if($existentLevelsCount == 0){
			$nav->$levels[0] = $this->config->firstLevelDefaultValue;
			$existentLevelsCount++;
		}
		
		// If we haven't gone to the end duplicate last level
		if($existentLevelsCount < count($levels)){
			$lastLevelValue = $nav->{$levels[$existentLevelsCount-1]};
			$nav->{$levels[$existentLevelsCount]} = $lastLevelValue;
		}
		
		// Get action if exists
		if(isset($_GET[$this->config->actionName]) and preg_match($this->config->validationRegExp, $_GET[$this->config->actionName])){
			$nav->{$this->config->actionName} = $_GET[$this->config->actionName];
		}
		
		$nav->existentLevelsCount = $existentLevelsCount;
		
		return $nav;
	}
	
	public function setFirstLevelDefaultValue($value){
		$this->firstLevelDefaultValue = $value;
	}
}
?>