<?
abstract class CometEventHandler{
	
	protected $params = array();
	
	protected $name = null;
	
	public $isBroadcast = false;
	
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
	
	/**
	 * @param array $params Receives linear array of single event data
	 */
	public function isAnyData($params){
		return true;
	}
	
	/**
	 * @param array $params Receives 2 dimensional array of all event datas that corresponds to given handler
	 */
	abstract public function getDataArray($params);
}
?>