<?php
class FileUploader
{
	const STORAGE_PROVIDER_FILESYSTEM = 'filesystem';
	const STORAGE_PROVIDER_S3 = 's3';
	
	public static function upload($file, $fileName = null, Config $customConfig = null){
		$config = ConfigManager::mergeConfigs($customConfig, ConfigManager::getConfig("File", "FileUploader")->AuxConfig);
		
		if($config->storageProvider === self::STORAGE_PROVIDER_FILESYSTEM){
			$uploadDir = $config->uploadDir;

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

				return move_uploaded_file($tmpName, $uploadDir . basename($fileName));
			}
		}
		elseif($config->storageProvider === self::STORAGE_PROVIDER_S3){
			if($file["error"] == UPLOAD_ERR_OK) {
				S3Transport::upload($file["tmp_name"], ensurePathLastSlash($config->S3Config->path) . $fileName, $config->S3Config->acl, $config->S3Config->configName);
				return true;
			}
		}
		return false;
	}
	
	public static function getFileContents($fileName, Config $customConfig = null){
		$config = ConfigManager::mergeConfigs($customConfig, ConfigManager::getConfig("File", "FileUploader")->AuxConfig);
		
		$return = [
			'length' => 0,
			'body' => ''
		];
		
		if($config->storageProvider === self::STORAGE_PROVIDER_FILESYSTEM){
		
			$uploadDir = $config->uploadDir;
			if(empty($uploadDir)){
				throw new RuntimeException("Unable to get any appropriate uploadDir!");
			}
			if(!file_exists($uploadDir)){
				throw new InvalidArgumentException("Upload directory $uploadDir doesn't exists.");
			}

			ensurePathLastSlash($uploadDir);
			
			$return['length'] = filesize($uploadDir . $fileName);
			$return['body'] = file_get_contents($uploadDir . $fileName);
			return $return;
		}
		elseif($config->storageProvider === self::STORAGE_PROVIDER_S3){
			$result = S3Transport::get(ensurePathLastSlash($config->S3Config->path) . $fileName, $config->S3Config->configName);
			$return['length'] = $result['ContentLength'];
			$return['body'] = $result['Body'];
			return $return;
		}
		
		return false;
	}
	
	public static function deleteFile($fileName, Config $customConfig = null){
		$config = ConfigManager::mergeConfigs($customConfig, ConfigManager::getConfig("File", "FileUploader")->AuxConfig);
		
		if($config->storageProvider === self::STORAGE_PROVIDER_FILESYSTEM){
		
			$uploadDir = $config->uploadDir;
			if(empty($uploadDir)){
				throw new RuntimeException("Unable to get any appropriate uploadDir!");
			}
			if(!file_exists($uploadDir)){
				throw new InvalidArgumentException("Upload directory $uploadDir doesn't exists.");
			}
			
			ensurePathLastSlash($uploadDir);

			@unlink($uploadDir . $fileName);
			return true;
		}
		elseif($config->storageProvider === self::STORAGE_PROVIDER_S3){
			S3Transport::delete(ensurePathLastSlash($config->S3Config->path) . $fileName, $config->S3Config->configName);
			return true;
		}
		
		return false;
	}
}
