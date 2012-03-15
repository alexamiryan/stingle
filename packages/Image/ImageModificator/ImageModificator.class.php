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
				$cropSettings = static::getDefaultCropSettings($image, $cropConfig->ratio);
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
	public function saveCropSettings(Image $image, $modelName, Config $cropSettings){
		if(!isset($this->config->imageModels->$modelName)){
			throw new InvalidArgumentException("There is no such image model with name $modelName");
		}
		
		if(empty($image->fileName)){
			throw new ImageModificatorException("Image is not initialized!");
		}
		
		list($imageW, $imageH) = $image->getDimensions();
		list($ratioW, $ratioH) = explode(":", $this->config->imageModels->$modelName->actions->{self::ACTION_CROP}->ratio);
		
		if($cropSettings->x + $cropSettings->width > $imageW or $cropSettings->y + $cropSettings->height > $imageH){
			throw new InvalidArgumentException("Crop window is not fitting into image!");
		}
		
		if($cropSettings->height == 0 or round($cropSettings->width/$cropSettings->height) != round($ratioW/$ratioH)){
			throw new InvalidArgumentException("Given crop window is not at needed ratio!");
		}
		
		
		$this->query->exec("INSERT INTO `".Tbl::get("TBL_CROP_SETTINGS")."`
								(`model_name`, `filename`, `x`, `y`, `width`, `height`)
								VALUES ('$modelName', '{$image->fileName}', '{$cropSettings->x}', '{$cropSettings->y}', '{$cropSettings->width}', '{$cropSettings->height}')
							ON DUPLICATE KEY UPDATE `x`='{$cropSettings->x}', `y`='{$cropSettings->y}', `width`='{$cropSettings->width}', `height`='{$cropSettings->height}'");
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
								WHERE `filename` = '$fileName'");
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
	public static function getDefaultCropSettings(Image $image, $ratio){
		if(!preg_match('/^[1-9]\d*\:[1-9]\d*$/', $ratio)){
			throw new InvalidArgumentException("Invalid ratio specified. Ratio should be in D:D format.");
		}
		
		list($imageW, $imageH) = $image->getDimensions();
		list($ratioW, $ratioH) = explode(":", $ratio);
		
		$x = 0;
		$y = 0;
		$width = 0;
		$height = 0;
		
		$widthUnchanged = false;
		$heightUnchanged = false;
		
		$derivedW = floor($imageH * $ratioW / $ratioH);
		$derivedH = floor($imageW * $ratioH / $ratioW);
		
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
			$y = floor(($imageH - $derivedH)/2);
			$width = $imageW;
			$height = $derivedH;
		}
		elseif($heightUnchanged === true){
			$x = floor(($imageW - $derivedW)/2);
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
	public static function getProportionalCropCords(Image $mainImage, Image $proportionalImage, Config $mainImageCropSettings){
		list($mainWidth, $mainHeight) = $mainImage->getDimensions();
		list($propWidth, $propHeight) = $proportionalImage->getDimensions();
		
		$factorW = $propWidth / $mainWidth;		
		$factorH = $propHeight / $mainHeight;		
		$initCoordsConfig = new Config(); 
		$initCoordsConfig->x = floor($factorW * $mainImageCropSettings->x);
		$initCoordsConfig->y = floor($factorH * $mainImageCropSettings->y);
		$initCoordsConfig->width = floor($factorW * $mainImageCropSettings->width);
		$initCoordsConfig->height = floor($factorH * $mainImageCropSettings->height);
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
	public function getCropMinSize($modelName){
		if(!isset($this->config->imageModels->$modelName)){
			throw new InvalidArgumentException("There is no such image model with name $modelName");
		}
		
		$smallSideMinSize = $this->config->imageModels->$modelName->actions->crop->smallSideMinSize;
		$ratio = $this->config->imageModels->$modelName->actions->crop->ratio;
		
		list($ratioW, $ratioH) = explode(":", $ratio);
		
		
		$cropMinSize = new Config();
		if($ratioW >= $ratioH){
			$cropMinSize->width = floor($ratioW/$ratioH * $smallSideMinSize);
			$cropMinSize->height = $smallSideMinSize;
		}
		else{
			$cropMinSize->width = $smallSideMinSize;
			$cropMinSize->height = floor($ratioH/$ratioW * $smallSideMinSize);
		}
		
		return $cropMinSize;
	}
	
	/**
	 * Get proportional min sizes for crop. Can be used to define minin 
	 * crop size for proportional resized image.
	 * 
	 * @param Image $originalImage
	 * @param Image $proportionalImage
	 * @param string $modelName
	 * @throws InvalidArgumentException
	 * @return Config
	 */
	public function getProportionalCropMinSize(Image $originalImage, Image $proportionalImage, $modelName){
		if(!isset($this->config->imageModels->$modelName)){
			throw new InvalidArgumentException("There is no such image model with name $modelName");
		}
		
		$smallSideMinSize = $this->config->imageModels->$modelName->actions->crop->smallSideMinSize;
		$ratio = $this->config->imageModels->$modelName->actions->crop->ratio;
		
		list($origWidth, $origHeight) = $originalImage->getDimensions();
		list($propWidth, $propHeight) = $proportionalImage->getDimensions();
		list($ratioW, $ratioH) = explode(":", $ratio);
		
		$coefficientW = $origWidth/$propWidth;
		$coefficientH = $origHeight/$propHeight;
		
		$cropMinSize = new Config();
		if($origWidth >= $origHeight){
			$cropMinSize->width = floor($ratioW/$ratioH * $smallSideMinSize / $coefficientW);
			$cropMinSize->height = floor($smallSideMinSize / $coefficientH);
		}
		else{
			$cropMinSize->width = floor($smallSideMinSize / $coefficientW);
			$cropMinSize->height = floor($ratioH/$ratioW * $smallSideMinSize / $coefficientH);
		}
		
		return $cropMinSize;
	}
}
?>