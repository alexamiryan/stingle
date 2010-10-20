<?
class ImageManager
{
	protected $config;
	
	protected $forceGenerate = false;
	
	public function __construct(Config $config){
		$this->config = $config;
		
		ensurePathLastSlash($this->config->cacheDir);
		ensurePathLastSlash($this->config->dataDir);
	}
	
	public function generateImageCache($sizeName, $fileName, $forceGenerate = null){
		if($forceGenerate === null){
			$forceGenerate = $this->forceGenerate;
		}
		
		if(!isset($this->config->sizes->$sizeName)){
			throw new InvalidArgumentException("There is no such size defined with name $sizeName!");
		}
		if(!file_exists($this->config->cacheDir . $sizeName)){
			throw new RuntimeException("There is no such folder $sizeName in cache directory!");
		}
		if(!file_exists($this->config->dataDir . $fileName)){
			throw new RuntimeException("There is no such image $fileName in data directory!");
		}
		
		$resultingFilePath = $this->config->cacheDir . $sizeName . "/" . $fileName;
		
		if($forceGenerate or !file_exists($resultingFilePath)){
			$image = new ImageManipulator($this->config->dataDir . $fileName);
			
			if($image->checkDimensions($this->config->sizes->$sizeName->width, $this->config->sizes->$sizeName->height) == ImageManipulator::DIMENSIONS_LARGER){
				$image->resize($this->config->sizes->$sizeName->width, $this->config->sizes->$sizeName->height);
			}
			
			$image->writeJpeg($resultingFilePath);
		}
		
		return $resultingFilePath;
	}
	
	public function forceGenerate($bool){
		$this->forceGenerate = $bool;
	}
}
?>