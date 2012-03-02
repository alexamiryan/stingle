<?
class ImageModificator extends DbAccessor
{
	protected $config;
	
	const TBL_CROP_SETTINGS = 'image_crop_settings';
	
	const ACTION_CROP = 'crop';
	const ACTION_RESIZE = 'resize';
	const ACTION_STAMP = 'stamp';
	
	public function __construct(Config $config, $dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
		$this->config = $config;
	}
	
	
	/**
	 * Make modifications on given Image object accrding to config
	 * 
	 * @param Image $image
	 * @param string $modelName
	 * @throws ImageModificatorException
	 * @throws InvalidArgumentException
	 */
	public function modify(Image $image, $modelName){
		if(empty($image->fileName)){
			throw new ImageModificatorException("Image is not initialized!");
		}
		
		if(!isset($this->config->imageModels->$modelName)){
			throw new InvalidArgumentException("There is no such image model with name $modelName");
		}
		
		foreach($this->config->imageModels->$modelName->actions as $actionName => $actionConfig){
			switch ($actionName){
				case self::ACTION_CROP:
					$this->cropImage($image, $modelName, $actionConfig);
					break;
				case self::ACTION_RESIZE:
					$this->resizeImage($image, $actionConfig);
					break;
				case self::ACTION_STAMP:
					$this->stampImage($image, $actionConfig);
					break;
			}
		}
	}
	
	/**
	 * Crop Image
	 * 
	 * @param Image $image
	 * @param string $modelName
	 * @param Config $cropConfig
	 * @throws ImageModificatorException
	 */
	protected function cropImage(Image $image, $modelName, Config $cropConfig){
		$this->query->exec("SELECT `x`, `y`, `width`, `height` FROM `".Tbl::get("TBL_CROP_SETTINGS")."` 
								WHERE `model_name` = '$modelName' and `filename` = '".$image->fileName."'");
		
		if($this->query->countRecords() == 1){
			$cropSettings = new Config($this->query->fetchRecord());
		}
		else{
			if(isset($cropConfig->applyDefaultCrop) and $cropConfig->applyDefaultCrop == true){
				$cropSettings = static::getDefaultCropSettings($image, $cropConfig);
			}
			else{
				throw new ImageModificatorException("Crop settings not found for image {$image->fileName}");
			}
		}
		
		if(!($cropSettings->x >= 0 and $cropSettings->y >= 0 and $cropSettings->width > 0 and $cropSettings->height > 0)){
			throw new ImageModificatorException("Crop settings are incorrect for image {$image->fileName}");
		}
		
		$image->crop($cropSettings->x, $cropSettings->y, $cropSettings->width, $cropSettings->height);
	}
	
	/**
	 * Get saved crop settings from DB
	 * 
	 * @param Image $image
	 * @param string $modelName
	 * @return Config|false
	 */
	public function getCropSettings(Image $image, $modelName){
			$this->query->exec("SELECT `x`, `y`, `width`, `height` FROM `".Tbl::get("TBL_CROP_SETTINGS")."` 
									WHERE `model_name` = '$modelName' and `filename` = '".$image->fileName."'");
			
			if($this->query->countRecords() == 1){
				return new Config($this->query->fetchRecord());
			}
			
			return false;
	}
	
	/**
	 * Save crop settings for given Image
	 * 
	 * @param Image $image
	 * @param string $modelName
	 * @param integer $x
	 * @param integer $y
	 * @param integer $width
	 * @param integer $height
	 * @throws InvalidArgumentException
	 * @throws ImageModificatorException
	 */
	public function saveCropSettings(Image $image, $modelName, $x, $y, $width, $height){
		if(!isset($this->config->imageModels->$modelName)){
			throw new InvalidArgumentException("There is no such image model with name $modelName");
		}
		
		if(empty($image->fileName)){
			throw new ImageModificatorException("Image is not initialized!");
		}
		
		list($imageW, $imageH) = $image->getDimensions();
		list($ratioW, $ratioH) = explode(":", $this->config->imageModels->$modelName->actions->{self::ACTION_CROP}->ratio);
		
		if(!($width+$x <= $imageW) or !($height+$y <= $imageH)){
			throw new InvalidArgumentException("Crop window is not fitting into image!");
		}
		
		if(round($width/$height) != round($ratioW/$ratioH)){
			throw new InvalidArgumentException("Given crop window is not at needed ratio!");
		}
		
		
		$this->query->exec("INSERT INTO `".Tbl::get("TBL_CROP_SETTINGS")."`
								(`model_name`, `filename`, `x`, `y`, `width`, `height`)
								VALUES ('$modelName', '{$image->fileName}', '$x', '$y', '$width', '$height')
							ON DUPLICATE KEY UPDATE `x`='$x', `y`='$y', `width`='$width', `height`='$height'");
	}
	
	/**
	 * Delete saved crop settings from DB 
	 * 
	 * @param string $fileName
	 * @throws InvalidArgumentException
	 */
	public function deleteCropSettings($fileName){
		if(empty($fileName)){
			throw new InvalidArgumentException("\$fileName have to be non empty string");
		}
		
		$this->query->exec("DELETE FROM `".Tbl::get("TBL_CROP_SETTINGS")."` 
								WHERE `filename` = '".$image->fileName."'");
	}
	
	/**
	 * Resize image
	 * 
	 * @param Image $image
	 * @param Config $resizeConfig
	 */
	protected function resizeImage(Image $image, Config $resizeConfig){
		if($image->checkDimensions($resizeConfig->width, $resizeConfig->height) == Image::DIMENSIONS_LARGER){
			$image->resize($resizeConfig->width, $resizeConfig->height);
		}
	}
	
	/**
	 * Make stamp(watermark) on image
	 * 
	 * @param Image $image
	 * @param Config $stampConfig
	 * @throws ImageModificatorException
	 * @throws InvalidArgumentException
	 */
	protected function stampImage(Image $image, Config $stampConfig){
		if(!file_exists($stampConfig->stampFile)){
			throw new ImageModificatorException("Can't locate stamp file. File not found!");
		}
		
		if(!in_array($stampConfig->corner, Image::getConstsArray("CORNER"))){
			throw new InvalidArgumentException("Invalid corner specified!");
		}
		
		$image->makeStamp($stampConfig->stampFile, $stampConfig->corner, (isset($stampConfig->alpha) ? $stampConfig->alpha : null)); 
	}
	
	/**
	 * Get default crop settings for image. 
	 * Default crop is on the center and max available size.
	 * 
	 * @param Image $image
	 * @param Config $cropConfig
	 * @return Config
	 */
	public static function getDefaultCropSettings(Image $image, Config $cropConfig){
		if(!isset($cropConfig->ratio)){
			throw new InvalidArgumentException("Ratio is not specified to crop image {$image->fileName}");
		}
		if(!preg_match('/^[1-9]\d*\:[1-9]\d*$/', $cropConfig->ratio)){
			throw new InvalidArgumentException("Invalid ratio specified. Ratio should be in D:D format.");
		}
		
		list($imageW, $imageH) = $image->getDimensions();
		list($ratioW, $ratioH) = explode(":", $cropConfig->ratio);
		
		$x = 0;
		$y = 0;
		$width = 0;
		$height = 0;
		
		$widthUnchanged = false;
		$heightUnchanged = false;
		
		$derivedW = round($imageH * $ratioW / $ratioH);
		$derivedH = round($imageW * $ratioH / $ratioW);
		
		if($ratioW >= $ratioH){
			if($imageW >= $imageH){
				if($derivedH <= $imageH){
					$widthUnchanged = true;
				}
				else{
					$heightUnchanged = true;
				}
			}
			else{
				$widthUnchanged = true;
			}
		}
		else{
			if($imageW >= $imageH){
				$heightUnchanged = true;
			}
			else{
				if($derivedW <= $imageW){
					$heightUnchanged = true;
				}
				else{
					$widthUnchanged = true;
				}
			}
		}
		
		if($widthUnchanged === true){
			$x = 0;
			$y = round(($imageH - $derivedH)/2);
			$width = $imageW;
			$height = $derivedH;
		}
		elseif($heightUnchanged === true){
			$x = round(($imageW - $derivedW)/2);
			$y = 0;
			$width = $derivedW;
			$height = $imageH;
		}
		
		$cropSettings = new Config();
		
		$cropSettings->x = $x;
		$cropSettings->y = $y;
		$cropSettings->width = $width;
		$cropSettings->height = $height; 
		
		return $cropSettings;
	}
	
	/**
	 * Get proportional crop settings for 
	 * initializing UI crop object
	 * 
	 * @param Image $mainImage
	 * @param Image $proportionalImage
	 * @param Config $mainImageCropSettings
	 * @return Config
	 */
	public static function getProportionalCropInitCords(Image $mainImage, Image $proportionalImage, Config $mainImageCropSettings){
		list($mainWidth, $mainHeight) = $mainImage->getDimensions();
		list($propWidth, $propHeight) = $proportionalImage->getDimensions();
		
		$factor = round(($propWidth / $mainWidth),20);		
		$initCoordsConfig = new Config(); 
		$initCoordsConfig->x = ceil($factor * $mainImageCropSettings->x);
		$initCoordsConfig->y = ceil($factor * $mainImageCropSettings->y);
		$initCoordsConfig->width = ceil($factor * $mainImageCropSettings->width);
		$initCoordsConfig->height = ceil($factor * $mainImageCropSettings->height);
		
		return $initCoordsConfig;
	}
	
	/**
	 * Get crop window minimum size
	 * 
	 * @param Image $image
	 * @param string $modelName
	 * @throws InvalidArgumentException
	 * @return Config
	 */
	public function getCropMinSize(Image $image, $modelName){
		if(!isset($this->config->imageModels->$modelName)){
			throw new InvalidArgumentException("There is no such image model with name $modelName");
		}
		
		$smallSideMinSize = $this->config->imageModels->$modelName->actions->crop->smallSideMinSize;
		$ratio = $this->config->imageModels->$modelName->actions->crop->ratio;
		
		list($imageWidth, $imageHeight) = $image->getDimensions();
		list($ratioW, $ratioH) = explode(":", $ratio);
		
		
		$cropMinSize = new Config();
		if($imageWidth >= $imageHeight){
			$cropMinSize->width = round($ratioW/$ratioH * $smallSideMinSize);
			$cropMinSize->height = $smallSideMinSize;
		}
		else{
			$cropMinSize->width = $smallSideMinSize;
			$cropMinSize->height = round($ratioH/$ratioW * $smallSideMinSize);
		}
		
		return $cropMinSize;
	}
}
?>