<?
abstract class CometChunk{
	
	protected $isAnyData = false;
	protected $name;
	
	protected function setIsAnyData(){
		$this->isAnyData = true;
	}
	
	public function isAnyData(){
		return $this->isAnyData;
	}
	
	protected function setName($name){
		$this->name = $name;
	}
	
	public function getName(){
		return $this->name;
	}
	
	abstract public function run();

	abstract public function getDataArray();
}
?>