<?
class Hook{
	private $name;
	private $method;
	private $object = null;
	
	public function __construct($hookName, $method, $object = null){
		if(empty($hookName)){
			throw new InvalidArgumentException("Hook name is empty!");
		}
		if(empty($method)){
			throw new InvalidArgumentException("Hook method is empty!");
		}
		
		$this->name = $hookName;
		$this->method = $method;
		$this->object = $object;
	}
	
	public function getName(){
		return $this->name;
	}
	
	public function getMethod(){
		return $this->method;
	}
	
	public function getObject(){
		return $this->object;
	}
}
?>