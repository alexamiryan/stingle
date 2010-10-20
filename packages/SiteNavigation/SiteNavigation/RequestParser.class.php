<?
class RequestParser
{
	private $firstLevelName;
	private $secondLevelName;
	private $firstLevelDefaultValue;
	private $secondLevelDefaultValue;
	private $actionName;
	private $validationRegExp;
	
	private $firstLevel;
	private $secondLevel;
	private $action;
	
	public function __construct(Config $config){
		$this->firstLevelName = $config->firstLevelName;
		$this->secondLevelName = $config->secondLevelName;
		
		$this->firstLevelDefaultValue = $config->firstLevelDefaultValue;
		$this->secondLevelDefaultValue = $config->secondLevelDefaultValue;
		
		$this->actionName = $config->actionName;

		$this->validationRegExp = $config->validationRegExp;
	}
	
	public function parse(){
		$nav = new Nav();
		$nav->{$this->firstLevelName} = $_GET[$this->firstLevelName];
		$nav->{$this->secondLevelName} = $_GET[$this->secondLevelName];
		$nav->{$this->actionName} = $_GET[$this->actionName];

		if(empty($nav->{$this->firstLevelName})){
			$nav->{$this->firstLevelName} = $this->firstLevelDefaultValue;
			$nav->{$this->secondLevelName} = $this->secondLevelDefaultValue;
		}
		
		if(!preg_match($this->validationRegExp, $nav->{$this->firstLevelName})){
			$nav->{$this->firstLevelName} = $this->firstLevelDefaultValue;
		}
		
		if(!preg_match($this->validationRegExp, $nav->{$this->secondLevelName})){
			$nav->{$this->secondLevelName} = $nav->{$this->firstLevelName};
		}
		
		if(!preg_match($this->validationRegExp, $nav->{$this->actionName})){
			$nav->{$this->actionName} = '';
		}
		
		if(empty($nav->{$this->secondLevelName})){
			$nav->{$this->secondLevelName} = $nav->{$this->firstLevelName};
		}

		return $nav;
	}
	
	public function setFirstLevelDefaultValue($value){
		$this->firstLevelDefaultValue = $value;
	}
	
	public function setSecondLevelDefaultValue($value){
		$this->secondLevelDefaultValue = $value;
	}
}
?>