<?
abstract class CometEventHandler{
	
	protected $params = array();
	
	protected $name = null;
	
	public function __construct($params = array()){
		if($this->name === null or empty($this->name)){
			throw new RuntimeException("Chunk name can't be empty!");
		}
		
		$this->params = $params;
	}
	
	protected function setName($name){
		$this->name = $name;
	}
	
	protected function setParams($params){
		if(empty($params) or !is_array($params)){
			throw new InvalidArgumentException("\$params have to be non empty array");
		}
		$this->params = $params;
	}
	
	protected function mergeParams($params){
		if(!is_array($params)){
			throw new InvalidArgumentException("\$params have to be array");
		}
		foreach($params as $key=>$value){
			$this->params[$key] = $value;
		}
	}
	
	public function getName(){
		return $this->name;
	}
	
	public function isAnyData($params){
		return true;
	}
	
	abstract public function getDataArray($params);
}
?>