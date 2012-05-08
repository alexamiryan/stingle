<?
class GenericCometChunk extends CometChunk{
	
	public $lastCheckTime;
	const TIMEOUT = 2;
	
	public function __construct(){
		$this->setName('dummy');
		$this->lastCheckTime = time();
	}
	
	public static function causeOutput(){
		$_SESSION['cometFinish'] = true;
	}
	
	public function run(){
		if(time() - $this->lastCheckTime >= self::TIMEOUT){
			session_start();
			if(isset($_SESSION['cometFinish']) and $_SESSION['cometFinish'] == true){
				unset($_SESSION['cometFinish']);
				$this->setIsAnyData();
			}
			session_write_close();
			$this->lastCheckTime = time();
		}
	}
	
	public function getDataArray(){
		return array();
	}
}
?>