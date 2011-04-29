<?
class FileUploader
{
	public static function upload($file, $fileName = null, $uploadDir = null){
		if($uploadDir === null){
			$fileUploaderConfig = ConfigManager::getConfig("FileUploader", "FileUploader")->AuxConfig;
			if(isset($fileUploaderConfig->uploadDir)){
				$uploadDir = $fileUploaderConfig->uploadDir;
			}
		}
		
		if(empty($uploadDir)){
			throw new RuntimeException("Unable to get any appropriate uploadDir!");
		}
		if(!file_exists($uploadDir)){
			throw new InvalidArgumentException("Upload directory $uploadDir doesn't exists.");
		}
		
		ensurePathLastSlash($uploadDir);
		
		if($file["error"] == UPLOAD_ERR_OK) {
	        $tmpName = $file["tmp_name"];
	        if($fileName === null){
	        	$fileName = $file["name"];
	        }
	        
	        return move_uploaded_file($tmpName, $this->uploadDir . basename($fileName));
	    }
	    return $fileName;
	}
	
	public static function deleteFile($fileName, $uploadDir = null){
		if($uploadDir === null){
			$fileUploaderConfig = ConfigManager::getConfig("FileUploader", "FileUploader")->AuxConfig;
			if(isset($fileUploaderConfig->uploadDir)){
				$uploadDir = $fileUploaderConfig->uploadDir;
			}
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
}
?>