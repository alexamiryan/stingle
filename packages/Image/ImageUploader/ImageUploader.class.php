<?
class ImageUploader
{
	const IMAGE_TYPE_JPEG 	= 'jpeg';
	const IMAGE_TYPE_PNG 	= 'png';
	const IMAGE_TYPE_GIF 	= 'gif';
	
	const EXCEPTION_IMAGE_IS_SMALL = 1;
	
	public static function upload($file, $fileName = null, $uploadDir = null){
		$imageUploaderConfig = ConfigManager::getConfig("Image", "ImageUploader");
		if($uploadDir === null and isset($imageUploaderConfig->uploadDir)){
			$uploadDir = $imageUploaderConfig->uploadDir;
		}
		
		if(empty($uploadDir)){
			throw new RuntimeException("Unable to get any appropriate uploadDir!");
		}
		if(!file_exists($uploadDir)){
			throw new InvalidArgumentException("Upload directory $uploadDir doesn't exists.");
		}
		ensurePathLastSlash($uploadDir);
		
	    if($fileName === null){
			$fileName = static::findNewFileName($uploadDir, $imageUploaderConfig->saveFormat);
	    }

	    if (!in_array($file["type"], $imageUploaderConfig->acceptedMimeTypes->toArray())){
	    	throw new ImageException("Unsupported file uploaded!");
	    }
	    
	    // Check if we are able to create image resource from this file.
	    $image = new ImageManipulator($file['tmp_name']);
	    
	    $savePath = $uploadDir . $fileName;
	    
	    if(isset($imageUploaderConfig->minimumSize)){
	    	$checkResult = $image->isSizeMeetRequirements(	$imageUploaderConfig->minimumSize->largeSideMinSize, 
	    													$imageUploaderConfig->minimumSize->smallSideMinSize);
	    	if(!$checkResult){
	    		throw new ImageUploaderException("Given image is smaller than specified minimum size.", static::EXCEPTION_IMAGE_IS_SMALL);
	    	}
	    }
	    
		switch($imageUploaderConfig->saveFormat){
			case self::IMAGE_TYPE_JPEG:
				$image->writeJpeg($savePath);
				break;
			case self::IMAGE_TYPE_PNG:
				$image->writePng($savePath);
				break;
			case self::IMAGE_TYPE_GIF:
				$image->writeGif($savePath);
				break;
		}
	    
	    return $fileName;
	}
	
	public static function deleteImage($fileName, $uploadDir = null){
		$imageUploaderConfig = ConfigManager::getConfig("Image", "ImageUploader");
		if($uploadDir === null and isset($imageUploaderConfig->uploadDir)){
			$uploadDir = $imageUploaderConfig->uploadDir;
		}
		
		if(empty($uploadDir)){
			throw new RuntimeException("Unable to get any appropriate uploadDir!");
		}
		if(!file_exists($uploadDir)){
			throw new InvalidArgumentException("Upload directory $uploadDir doesn't exists.");
		}
		
		$imagePath = $uploadDir . $fileName;
		@unlink($imagePath);
	}
	
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
	
	private static function generateUniqueFileName(){
		return md5(uniqid(rand(), true));
	}
	
	private static function getAppropriateFileExtension($imageFormat){
		switch($imageFormat){
			case self::IMAGE_TYPE_JPEG:
				return '.jpg';
				break;
			case self::IMAGE_TYPE_PNG:
				return '.png';
				break;
			case self::IMAGE_TYPE_GIF:
				return '.gif';
				break;
		}
	}
}
?>