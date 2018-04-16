<?php
/**
 * Memcache CacheResource
 *
 * CacheResource Implementation based on the KeyValueStore API to use
 * memcache as the storage resource for Smarty's output caching.
 *
 * Note that memcache has a limitation of 256 characters per cache-key.
 * To avoid complications all cache-keys are translated to a sha1 hash.
 *
 * @package CacheResource-examples
 * @author Rodney Rehm
 */
class Smarty_CacheResource_Memcache extends Smarty_CacheResource_KeyValueStore {
	/**
	 * memcache instance
	 * @var Memcache
	 */
	const TAG = 'smrt';
	
	protected $memcacheConfig = null;
	protected $memcache = null;

	public function __construct(){
		$this->memcacheConfig = ConfigManager::getConfig("Db", "Memcache")->AuxConfig;
		
		if(strpos($this->memcacheConfig->keyPrefix, ":")){
			throw new RuntimeException("Memcache key prefix can't contain colon \":\"!");
		}
		
		if($this->memcacheConfig->enabled){
			$this->memcache = new MemcacheWrapper($this->memcacheConfig->host, $this->memcacheConfig->port);
		}
	}

	/**
	 * Read values for a set of keys from cache
	 *
	 * @param array $keys list of keys to fetch
	 * @return array list of values with the given keys used as indexes
	 * @return boolean true on success, false on failure
	 */
	protected function read(array $keys){
		$memcacheKeys = array();
		$lookup = array();
		
		foreach ($keys as $key) {
			$keyHash = $this->memcache->getNamespacedKey(self::TAG . $this->memcache->getIdKey($key)) . ":" . sha1($key);
			$memcacheKeys[] = $keyHash;
			$lookup[$keyHash] = $key;
		}
		
		$finalResult = array();
		$result = $this->memcache->get($memcacheKeys);
		
		foreach ($result as $key => $value) {
			$finalResult[$lookup[$key]] = $value;
		}
		return $finalResult;
	}

	/**
	 * Save values for a set of keys to cache
	 *
	 * @param array $keys list of values to save
	 * @param int $expire expiration time
	 * @return boolean true on success, false on failure
	 */
	protected function write(array $keys, $expire=0){
		foreach ($keys as $key => $value) {
			$key = $this->memcache->getNamespacedKey(self::TAG . $this->memcache->getIdKey($key)) . ":" . sha1($key);
			$this->memcache->set($key, $value, $expire, 0);
		}
		return true;
	}

	/**
	 * Remove values from cache
	 *
	 * @param array $keys list of keys to delete
	 * @return boolean true on success, false on failure
	 */
	protected function delete(array $keys){
		foreach ($keys as $key) {
			$key = $this->memcache->getNamespacedKey(self::TAG . $this->memcache->getIdKey($key)) . ":" . sha1($key);
			$this->memcache->delete($key);
		}
		return true;
	}

	/**
	 * Remove *all* values from cache
	 *
	 * @return boolean true on success, false on failure
	 */
	protected function purge(){
		return $this->memcache->invalidateCacheByTag(self::TAG);
	}
	
}