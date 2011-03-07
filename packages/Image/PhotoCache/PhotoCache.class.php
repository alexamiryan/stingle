<?
class PhotoCache
{
	protected $config;
	
	protected $forceGenerate = false;
	
	public function __construct(Config $config){
		$this->config = $config;
		
		ensurePathLastSlash($this->config->cacheDir);
		ensurePathLastSlash($this->config->dataDir);
	}
	
	public function generateImageCache($sizeName, Photo $photo, $forceGenerate = null){
		if($forceGenerate === null){
			$forceGenerate = $this->forceGenerate;
		}
		
		if(!isset($this->config->sizes->$sizeName)){
			throw new InvalidArgumentException("There is no such size defined with name $sizeName!");
		}
		if(!file_exists($this->config->cacheDir . $sizeName)){
			throw new RuntimeException("There is no such folder $sizeName in cache directory!");
		}
		if(!file_exists($this->config->dataDir . $photo->fileName)){
			throw new RuntimeException("There is no such image $photo->fileName in data directory!");
		}
		
		$resultingFilePath = $this->config->cacheDir . $sizeName . "/" . $photo->fileName;
		
		if($forceGenerate or !file_exists($resultingFilePath)){
			$image = new ImageManipulator($this->config->dataDir . $photo->fileName);
			
			if(isset($this->config->sizes->$sizeName->needToCrop) and $this->config->sizes->$sizeName->needToCrop == true){
				$image->crop($photo->cropX, $photo->cropY, $photo->cropWidth, $photo->cropHeight);
			}
			
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
	
	/**
	 * Clear cache of given photo filename 
	 * @param string $fileName
	 */
	public function clearPhotoCache($fileName){
		if(empty($fileName)){
			throw new InvalidArgumentException("\$fileName is empty!");
		}
		foreach ($this->config->sizes as $sizeName => $sizeConfig){
			if(file_exists($this->config->cacheDir . $sizeName . "/" . $fileName)){
				@unlink($this->config->cacheDir . $sizeName . "/" . $fileName);
			}
		}
	}
}
?>