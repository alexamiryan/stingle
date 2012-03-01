<?
class ImageCache
{
	protected $config;
	
	public function __construct(Config $config){
		$this->config = $config;
		
		ensurePathLastSlash($this->config->cacheDir);
		ensurePathLastSlash($this->config->dataDir);
	}
	
	public function generateImageCache($folderName, Image $image, $forceGenerate = false){
		if(!isset($this->config->folders->$folderName)){
			throw new InvalidArgumentException("There is no such folder defined with name $folderName!");
		}
		if(!file_exists($this->config->cacheDir . $folderName)){
			throw new RuntimeException("There is no such folder $folderName in cache directory!");
		}
		
		$resultingFilePath = $this->config->cacheDir . $folderName . "/" . $image->fileName;
		
		if($forceGenerate or !file_exists($resultingFilePath)){
			$image->writeJpeg($resultingFilePath);
		}
		
		return $resultingFilePath;
	}
	
	/**
	 * Clear cache of given photo filename 
	 * @param string $fileName
	 */
	public function clearImageCache($fileName){
		if(empty($fileName)){
			throw new InvalidArgumentException("\$fileName is empty!");
		}
		foreach ($this->config->folders as $folderName){
			if(file_exists($this->config->cacheDir . $folderName . "/" . $fileName)){
				@unlink($this->config->cacheDir . $folderName . "/" . $fileName);
			}
		}
	}
}
?>