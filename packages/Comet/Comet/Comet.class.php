<?
class Comet{
	
	protected $chunks = array();
	
	private $runInteval;
	private $timeout;
	
	public function __construct($runInteval = null, $timeout = null){
		if(!empty($runInteval) and is_numeric($runInteval)){
			$this->runInteval = $runInteval;
		}
		else{
			$this->runInteval = ConfigManager::getConfig("Comet", "Comet")->AuxConfig->runInterval;
		}
		
		if(!empty($timeout) and is_numeric($timeout)){
			$this->timeout = $timeout;
		}
		else{
			$this->timeout = ConfigManager::getConfig("Comet", "Comet")->AuxConfig->timeout;
		}
	}
	
	public function addChunk(CometChunk $chunk){
		$this->chunks[$chunk->getName()] = $chunk;
	}
	
	public function isAnyData(){
		foreach($this->chunks as $chunkName => $chunk){
			if($chunk->isAnyData()){
				return true;
			}
		}
		return false;
	}
	
	public function run(){
		$startTime = time();

		session_write_close();
		
		while (true){
			foreach($this->chunks as $chunkName => $chunk){
				$chunk->run();
			}
		
			if($this->isAnyData() or time() - $startTime > $this->timeout){
				JSON::jsonOutput($this->getOutputArray());
				break;
			}
			usleep($this->runInteval*1000000);
		}
	}
	
	public function getOutputArray(){
		$output = array();
		
		foreach($this->chunks as $chunkName => $chunk){
			$output[$chunkName] = $chunk->getDataArray();
		}
		
		if(!empty($output)){
			return $output;
		}
		return false;
	}
}
?>