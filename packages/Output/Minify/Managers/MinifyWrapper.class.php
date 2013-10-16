<?php
class MinifyWrapper{
	
	public function __construct(){
		if (! function_exists('getSmartFile')) {
			function getSmartFile($file){
				$spl = explode("::", $file);
				return Reg::get('smarty')->fetch($spl[1]);
			}
		}
		
		if (! function_exists('remoteFetch')) {
			function remoteFetch($file) {
				$spl = explode("::", $file);
				return file_get_contents($spl[1]);
			}
		}
		
	}
	
	public function output(array $filesGroup){
		$sources = array();
		foreach($filesGroup as $fileGroup){
			foreach($fileGroup as $file){
				$path = null;
				$altPath = null;
		
				if(isset($file['path']) and !empty($file['path'])){
					$altPath = $file['path'];
				}
		
				switch($file['type']){
					case MinifySmartyWrapper::TYPE_JS:
						
						if((strpos($file['src'], "https://") === false and strpos($file['src'], "http://") === false) and substr($file['src'],0,1) != "/"){
							$path = Reg::get('smarty')->findFilePath("js/" . $file['src'], $altPath);
						}
						else{
							$sources[] = new Minify_Source(array(
									'id' => $file['src'],
									'getContentFunc' => 'remoteFetch',
									'contentType' => Minify::TYPE_JS,
									'lastModified' => ($_SERVER['REQUEST_TIME'] - $_SERVER['REQUEST_TIME'] % 86400)
							));
						}
						if(!empty($path)){
							array_push($sources, $path);
						}
						break;
					case MinifySmartyWrapper::TYPE_CSS:
						if((strpos($file['src'], "https://") === false and strpos($file['src'], "http://") === false) and substr($file['src'],0,1) != "/"){
							$path = Reg::get('smarty')->findFilePath("css/" . $file['src'], $altPath);
						}
						else{
							$sources[] = new Minify_Source(array(
									'id' => $file['src'],
									'getContentFunc' => 'remoteFetch',
									'contentType' => Minify::TYPE_CSS,
									'lastModified' => ($_SERVER['REQUEST_TIME'] - $_SERVER['REQUEST_TIME'] % 86400)
							));
						}
						if(!empty($path)){
							array_push($sources, $path);
						}
						break;
					case MinifySmartyWrapper::TYPE_JS_SMART:
						$path = Reg::get('smarty')->findFilePath("js/" . $file['src'], $altPath);
						$sources[] = new Minify_Source(array(
								'id' => $path,
								'getContentFunc' => 'getSmartFile',
								'contentType' => Minify::TYPE_JS,
								'lastModified' => filemtime($path)
						));
						break;
					case MinifySmartyWrapper::TYPE_CSS_SMART:
						$path = Reg::get('smarty')->findFilePath("css/" . $file['src'], $altPath);
						$sources[] = new Minify_Source(array(
								'id' => $path,
								'getContentFunc' => 'getSmartFile',
								'contentType' => Minify::TYPE_CSS,
								'lastModified' => filemtime($path)
						));
						break;
				}
			}
		}
		$min_serveController = new Minify_Controller_MinApp();
		
		$options = array(
				'files' => $sources,
				'maxAge' => 86400,
		);
		
		Minify::serve('Files', $options);
	}
	
	public function enableMemcache(){
		$this->memcacheConfig = ConfigManager::getConfig("Db", "Memcache")->AuxConfig;
		
		if($this->memcacheConfig->enabled){
			$memcache = new Memcache();
			if($memcache->pconnect($this->memcacheConfig->host, $this->memcacheConfig->port)){
				Minify::setCache(new Minify_Cache_Memcache($memcache));
			}
		}
	}
	
	public function enableFilesCache(){
		Minify::setCache(new Minify_Cache_File());		
	}
	
	public function enableLogging(){
		$min_errorLogger = FirePHP::getInstance(true);
		Minify_Logger::setLogger($min_errorLogger);
	}
	
}