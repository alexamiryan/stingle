<?
class ImageCache
{
	protected $config;
	
	public function __construct(Config $config){
		$this->config = $config;
		
		ensurePathLastSlash($this->config->cacheDir);
	}
	
	/**
	 * Generate image cache and return resulting path
	 * 
	 * @param string $folderName
	 * @param Image $image
	 * @param boolean $forceGenerate
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 * @return string
	 */
	public function generateImageCache($folderName, Image $image, $forceGenerate = false){
		if(!in_array($folderName, $this->config->folders->toArray())){
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
	 * Checks if image cache is present
	 * 
	 * @param string $folderName
	 * @param string $fileName
	 * @throws InvalidArgumentException
	 * @throws RuntimeException
	 * @return boolean
	 */
	public function isImageCached($folderName, $fileName){
		if(!in_array($folderName, $this->config->folders->toArray())){
			throw new InvalidArgumentException("There is no such folder defined with name $folderName!");
		}
		if(!file_exists($this->config->cacheDir . $folderName)){
			throw new RuntimeException("There is no such folder $folderName in cache directory!");
		}
		
		$resultingFilePath = $this->config->cacheDir . $folderName . "/" . $fileName;
		
		if(file_exists($resultingFilePath)){
			return true;
		}
		return false;
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
	
	/**
	 * Clear whole image cache 
	 */
	public function clearWholeImageCache(){
		foreach ($this->config->folders as $folderName){
			@unlink($this->config->cacheDir . $folderName . "/*");
		}
	}
}
?>