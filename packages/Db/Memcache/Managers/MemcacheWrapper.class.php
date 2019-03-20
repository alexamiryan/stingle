<?php

class MemcacheWrapper {

	const KEY_VERSION_PREFIX = 'kv';
	const MEMCACHE_OFF = 0;
	const MEMCACHE_UNLIMITED = -1;
	const MEMCACHE_DEFAULT = null;

	/**
	 *
	 * @var type Config
	 */
	protected $config = null;

	/**
	 * @var Memcache
	 */
	protected $memcache = null;

	/**
	 *
	 * @var boolean 
	 */
	protected $isEnabled = false;

	/**
	 * Class constructor
	 *
	 * @param Config $config
	 */
	public function __construct(Config $config) {
		$this->config = $config;

		$this->isEnabled = $this->config->enabled;

		if ($this->isEnabled) {
			$this->memcache = new Memcache();
			if (!$this->memcache->pconnect($this->config->host, $this->config->port)) {
				throw new MemcacheException("Error in object initialization", 2);
			}
		}
	}

	/**
	 * Get Memcache object
	 * 
	 * @return Memcache
	 */
	public function getMemcache() {
		return $this->memcache;
	}

	/**
	 * Put an object in memcache. if there's already an entry
	 * with the specified key it will be overwritten
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $expire the number of seconds after which cached item expires
	 * @param int $flag
	 * @return bool true if successful false otherwise
	 */
	public function set($key, $value, $expire = 0, $flag = 0) {
		if (empty($key)) {
			throw new InvalidArgumentException("\$key can't be empty");
		}
		if (!$this->isEnabled) {
			return false;
		}

		if (!$this->memcache->set($key, $value, $flag, $expire)) {
			throw new MemcacheException("Unable to set data to Memcache");
		}
	}

	/**
	 * get cache item with given key
	 *
	 * @param string $key
	 * @return string|array
	 */
	public function get($key) {
		if (empty($key)) {
			throw new InvalidArgumentException("\$key is empty");
		}

		if (!$this->isEnabled) {
			return false;
		}

		return $this->memcache->get($key);
	}

	/**
	 * Helper function for caching objects
	 * 
	 * @param string $tag
	 * @param mixed $value
	 * @param int $cacheMinutes
	 */
	public function setObject($tag, $name, $value, $cacheMinutes = self::MEMCACHE_UNLIMITED) {
		if ($cacheMinutes === self::MEMCACHE_OFF) {
			return;
		}
		if (!$this->isEnabled) {
			return false;
		}
		if(!empty($name)){
			$name = ':' . $name;
		}
		
		$key = $this->getNamespacedKey($tag) . $name;

		$this->set($key, $value, $this->getExpirationByCacheMinutes($cacheMinutes));
	}

	/**
	 * Helper function for getting cached objects
	 * @param string $tag
	 * @return mixed
	 */
	public function getObject($tag, $name = "") {
		if (!$this->isEnabled) {
			return false;
		}
		if(!empty($name)){
			$name = ':' . $name;
		}
		
		$key = $this->getNamespacedKey($tag) . $name;

		return $this->get($key);
	}

	/**
	 * Delete object from cache
	 * 
	 * @param string $tag
	 * @return boolean
	 */
	public function deleteObject($tag) {
		if (!$this->isEnabled) {
			return false;
		}
		$key = $this->getNamespacedKey($tag);

		return $this->delete($key);
	}

	public function getExpirationByCacheMinutes($cacheMinutes) {
		if ($cacheMinutes > 0) {
			$expire = time() + ($cacheMinutes * 60);
		}
		elseif ($cacheMinutes == self::MEMCACHE_UNLIMITED) {
			$expire = 0;
		}

		return $expire;
	}

	/**
	 * Increment cache item with given key
	 *
	 * @param string $key
	 * @return integer|false
	 */
	public function increment($key) {
		if (empty($key)) {
			throw new InvalidArgumentException("\$key is empty");
		}
		if (!$this->isEnabled) {
			return false;
		}

		return $this->memcache->increment($key);
	}

	/**
	 * Decrement cache item with given key
	 *
	 * @param string $key
	 * @return integer|false
	 */
	public function decrement($key) {
		if (empty($key)) {
			throw new InvalidArgumentException("\$key is empty");
		}
		if (!$this->isEnabled) {
			return false;
		}

		return $this->memcache->decrement($key);
	}

	/**
	 * Returns various server statistics
	 */
	public function getServerStats() {
		if (!$this->isEnabled) {
			return false;
		}
		return $this->memcache->getExtendedStats();
	}

	/**
	 * Clear all save items in Memcache
	 */
	public function clearAllItems() {
		if (!$this->isEnabled) {
			return false;
		}
		$this->memcache->flush();
	}

	/**
	 * Delete cache item with given key
	 * @param string $key
	 */
	public function delete($key) {
		if (empty($key)) {
			throw new InvalidArgumentException("\$key is empty");
		}
		if (!$this->isEnabled) {
			return false;
		}

		return $this->memcache->delete($key);
	}

	/**
	 * Get array of cache item's keys
	 * @return array
	 */
	public function getKeysList() {
		if (!$this->isEnabled) {
			return false;
		}

		$list = array();
		$allSlabs = $this->memcache->getExtendedStats('slabs');
		$items = $this->memcache->getExtendedStats('items');
		foreach ($allSlabs as $server => $slabs) {
			foreach ($slabs as $slabId => $slabMeta) {
				if (!is_numeric($slabId)) {
					continue;
				}
				$cdump = $this->memcache->getExtendedStats('cachedump', (int) $slabId, 1000000);
				foreach ($cdump as $server => $entries) {
					if ($entries) {
						foreach ($entries as $eName => $eData) {
							array_push($list, $eName);
						}
					}
				}
			}
		}
		ksort($list);

		return $list;
	}

	public function getNamespacedKey($tag = null) {
		$key = $this->getKeyBeginning($tag);

		$version = $this->get($this->getKeyBeginning($tag, true));
		if ($version == false or ! is_numeric($version)) {
			$version = 1;
		}

		$key .= $version;
		return $key;
	}

	public function invalidateCacheByTag($tag = null) {
		if (empty($tag)) {
			throw new InvalidArgumentException("\$tag should be non empty string");
		}
		if (!$this->isEnabled) {
			return false;
		}

		$versionKey = $this->getKeyBeginning($tag, true);

		if (!$this->increment($versionKey)) {
			$this->set($versionKey, 2);
		}
	}

	public function getIdKey($key) {
		$idTag = "";
		if (preg_match("/id[\:\_]([a-z]\d+?)\D/i", $key, $matches)) {
			$idTag = ':' . $matches[1];
		}

		return $idTag;
	}

	public function deleteKeys($classes, $globalPrefix = null) {
		if (!$this->isEnabled) {
			return false;
		}
		if (!is_array($classes)) {
			$classes = array($classes);
		}

		if ($globalPrefix === null) {
			$globalPrefix = $this->config->keyPrefix;
		}

		$list = $this->getKeysList();

		$count = 0;
		foreach ($list as $key) {
			$array = explode(":", $key);
			if (count($array) >= 2) {
				if ($array[0] == $globalPrefix and in_array($array[1], $classes)) {
					if ($this->delete($key)) {
						$count++;
					}
				}
			}
		}

		return $count;
	}

	protected function getKeyBeginning($tag = null, $isVersionKey = false) {
		$key = $this->config->keyPrefix . ":";

		if ($isVersionKey) {
			$key .= self::KEY_VERSION_PREFIX . ":";
		}

		if ($tag !== null) {
			$key .= $tag . ":";
		}
		else {
			$callingClass = "";
			$backtrace = debug_backtrace();
			if (isset($backtrace[4]['class'])) {
				$callingClass = $backtrace[4]['class'];
			}

			$key .= $callingClass . ":";
		}

		return $key;
	}

}
