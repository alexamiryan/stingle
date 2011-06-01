<?
class MemcacheWrapper
{
	private $memcache = null;

	/**
	 * Class constructor
	 *
	 * @param $memcache_host, $memcache_port
	 * @return wmemcache Object
	 * @version 1.0
	 */
	public function __construct($memcache_host='127.0.0.1', $memcache_port=11211) {
		if(is_null($this->memcache) || (!is_object($this->memcache))) {
			$this->memcache = new Memcache();
			if(!$this->memcache->pconnect($memcache_host, $memcache_port)){
				throw new Exception("Error in object initialization", 2);
			}
		}
	}

	/**
	 * put an object in memcache. if there's already an entry
	 * with the specified key it will be overwritten
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $expire the number of seconds after which cached item expires
	 * @param int $flag
	 * @return bool ture if successful false otherwise
	 */
	public function set($key, $value, $expire = 0, $flag = 0) {
		if(empty($key) or empty($value)){
			throw new InvalidArgumentException("\$key and \$value can't be empty");
		}
		
		if (!$this->memcache->set($key, $value, $flag, $expire)){
			throw new RuntimeException("Unable to set data to Memcache");
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $key
	 * @return unknown
	 */
	public function get($key) {
		if(empty($key)){
			throw new InvalidArgumentException("\$key is empty");
		}
		return $this->memcache->get($key);
	}
	
	/**
	 * Returns various server statistics
	 */
	public function getServerStats(){
		return $this->memcache->getExtendedStats();
	}
	
	/**
	 * Clear all save items in Memcache
	 */
	public function clearAllItems(){
		$this->memcache->flush();
	}
}
?>