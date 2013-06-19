<?php
class ImageUploader
{
	const EXCEPTION_IMAGE_IS_SMALL = 1;
	const EXCEPTION_IMAGE_IS_BIG = 2;
	
	/**
	 * Upload Image from POST
	 * 
	 * @param array $file - Part of $_FILES like $_FILES['photo']
	 * @param string $fileName - Put image with this filename
	 * @param Config $imageUploaderConfig
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 * @throws ImageException
	 * @throws ImageUploaderException
	 * @return Image
	 */
	public static function upload($file, $fileName = null, Config $imageUploaderConfig = null){
		$imageUploaderConfig = ConfigManager::mergeConfigs($imageUploaderConfig, ConfigManager::getConfig("Image", "ImageUploader")->AuxConfig);
		$uploadDir = $imageUploaderConfig->uploadDir;
		
		if(empty($uploadDir)){
			throw new RuntimeException("Unable to get any appropriate uploadDir!");
		}
		if(!file_exists($uploadDir)){
			throw new InvalidArgumentException("Upload directory $uploadDir doesn't exists.");
		}
		ensurePathLastSlash($uploadDir);
		
		$format = null;
		if(isset($imageUploaderConfig->saveFormat) and $imageUploaderConfig->saveFormat != null){
			$format = $imageUploaderConfig->saveFormat;
		}
		else{
			$format = $image->getType();
		}
		
	    if($fileName === null){
			$fileName = static::findNewFileName($uploadDir, $format);
	    }

	    if (!in_array($file["type"], $imageUploaderConfig->acceptedMimeTypes->toArray())){
	    	throw new ImageException("Unsupported file uploaded!");
	    }
	    
	    // Check if we have enough memory to open this file as image
	    $info = getimagesize($file['tmp_name']);
	    
	    if($info != false && !Image::checkMemAvailbleForResize($info[0], $info[1])){
	    	throw new ImageUploaderException("Not enough memory to open image", static::EXCEPTION_IMAGE_IS_BIG);
	    }
	    
	    // Check if we are able to create image resource from this file.
	    $image = new Image($file['tmp_name']);
	    
	    $savePath = $uploadDir . $fileName;
	    
	    if(isset($imageUploaderConfig->minimumSize)){
	    	$checkResult = $image->isSizeMeetRequirements(	$imageUploaderConfig->minimumSize->largeSideMinSize, 
	    													$imageUploaderConfig->minimumSize->smallSideMinSize);
	    	if(!$checkResult){
	    		throw new ImageUploaderException("Given image is smaller than specified minimum size.", static::EXCEPTION_IMAGE_IS_SMALL);
	    	}
	    }
	    
		switch($format){
			case Image::IMAGE_TYPE_JPEG:
				$image->writeJpeg($savePath);
				break;
			case Image::IMAGE_TYPE_PNG:
				$image->writePng($savePath);
				break;
			case Image::IMAGE_TYPE_GIF:
				$image->writeGif($savePath);
				break;
		}
	    
	    return $image;
	}
	
	/**
	 * Delete image from data dir
	 * 
	 * @param string $fileName
	 * @param string $uploadDir
	 * @param boolean $strict - Throw exception or not if image is not found
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 */
	public static function deleteImage($fileName, $uploadDir = null, $strict = false){
		$imageUploaderConfig = ConfigManager::getConfig("Image", "ImageUploader")->AuxConfig;
		if($uploadDir === null and isset($imageUploaderConfig->uploadDir)){
			$uploadDir = $imageUploaderConfig->uploadDir;
		}
		
		if(empty($uploadDir)){
			throw new RuntimeException("Unable to get any appropriate uploadDir!");
		}
		if(!file_exists($uploadDir)){
			throw new InvalidArgumentException("Upload directory $uploadDir doesn't exists.");
		}
		
		if(!file_exists($uploadDir . $fileName)){
			if($strict){
				throw new InvalidArgumentException("File $fileName in  directory $uploadDir doesn't exists.");
			}
			else{
				return;
			}
		}
		
		$imagePath = $uploadDir . $fileName;
		@unlink($imagePath);
	}
	
	/**
	 * Find new non conflicting filename
	 * 
	 * @param string $uploadDir
	 * @param string $imageFormat
	 */
	private static function findNewFileName($uploadDir, $imageFormat){
		$fileName = static::generateUniqueFileName() . static::getAppropriateFileExtension($imageFormat);
		while(true){
			if(file_exists($uploadDir . $fileName)){
				$fileName = static::generateUniqueFileName() . static::getAppropriateFileExtension($imageFormat);
			}
			else{
				break;
			}
		}
		return $fileName;
	}
	
	/**
	 * Generate unique filename
	 * 
	 * @return string
	 */
	private static function generateUniqueFileName(){
		return Crypto::secureRandom(256);
	}
	
	/**
	 * Get file extension by image type
	 * 
	 * @param string $imageFormat
	 * @return string
	 */
	private static function getAppropriateFileExtension($imageFormat){
		switch($imageFormat){
			case Image::IMAGE_TYPE_JPEG:
				return '.jpg';
				break;
			case Image::IMAGE_TYPE_PNG:
				return '.png';
				break;
			case Image::IMAGE_TYPE_GIF:
				return '.gif';
				break;
		}
	}
}
