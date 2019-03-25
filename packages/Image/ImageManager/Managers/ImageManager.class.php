<?php

class ImageManager {

	const STORAGE_PROVIDER_FILESYSTEM = 'filesystem';
	const STORAGE_PROVIDER_S3 = 's3';
	const EXCEPTION_IMAGE_IS_SMALL = 1;
	const EXCEPTION_IMAGE_IS_BIG = 2;

	/**
	 * Upload Image from POST
	 * 
	 * @param array $file - Part of $_FILES like $_FILES['photo']
	 * @param string $typeName - Image type name in Config
	 * @param string $fileName - Put image with this filename
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 * @throws ImageException
	 * @throws ImageManagerException
	 * @return Image
	 */
	public static function upload($file, $typeName, $fileName = null) {
		$config = static::initConfigs($typeName);
		
		// Check if file is in accepted MIME types list
		if (!in_array($file["type"], $config['acceptedMimeTypes'])) {
			throw new ImageException("Unsupported file uploaded!");
		}

		// Check if we have available memory to handle this file
		self::checkIsEnoughMemory($file['tmp_name']);

		// Check if we are able to create image resource from this file.
		$image = new Image($file['tmp_name']);
		$image->fixOrientation();

		// Get filename
		if ($fileName === null) {
			$fileName = static::findNewFileName($config['uploadDir'], $config['saveFormat']);
		}

		$originalImagePath = $config['uploadDir'] . $fileName;

		// Check if image is big enough
		if (isset($config['config']->minimumSize)) {
			$checkResult = $image->isSizeMeetRequirements($config['config']->minimumSize->largeSideMinSize, $config['config']->minimumSize->smallSideMinSize);
			if (!$checkResult) {
				throw new ImageManagerException("Given image is smaller than specified minimum size.", static::EXCEPTION_IMAGE_IS_SMALL);
			}
		}

		if (isset($config['config']->minimumSizeStreight)) {
			$checkResult = $image->isWidthHeightMeetRequirements($config['config']->minimumSizeStreight->minWidth, $config['config']->minimumSizeStreight->minHeight);
			if (!$checkResult) {
				throw new ImageManagerException("Given image is smaller than specified minimum size.", static::EXCEPTION_IMAGE_IS_SMALL);
			}
		}

		static::saveImage($image, $config['saveFormat'], $originalImagePath);
		unset($image);

		if ($config['cacheEnabled'] === true and $config['preGenerateCacheOnUpload'] === true) {
			static::pregenerateCacheForAllModels($fileName, $typeName);
		}

		if ($config['storageProvider'] == self::STORAGE_PROVIDER_S3) {
			static::storeInS3($originalImagePath, ensurePathLastSlash($config['s3Config']->originalFilesPath) . $fileName, $config['s3Config']->originalFileACL, $config['s3Config']->configName);
		}

		return $fileName;
	}
	
	public static function pregenerateCacheForAllModels($fileName, $typeName){
		$config = static::initConfigs($typeName);
		foreach (array_keys($config['config']->imageModels->toArray()) as $modelName) {
			static::generateCache($fileName, $typeName, $modelName);
		}
	}

	protected static function storeInS3($fromPath, $toPath, $acl, $s3ConfigName) {
		$result = S3Transport::upload($fromPath, $toPath, $acl, $s3ConfigName);
		unlink($fromPath);
		return $result;
	}

	protected static function downloadFromS3($fromPath, $toPath, $s3ConfigName) {
		if (S3Transport::fileExists($fromPath, $s3ConfigName)) {
			return S3Transport::download($fromPath, $toPath, $s3ConfigName);
		}
		throw new ImageManagerException("Error getting image from cache");
	}

	protected static function deleteFromS3($path, $s3ConfigName) {
		return S3Transport::delete($path, $s3ConfigName);
	}

	public static function generateCache($fileName, $typeName, $modelName) {
		$config = static::initConfigs($typeName, $modelName);
		if ($config['cacheEnabled'] === false) {
			return;
		}

		$originalImagePath = $config['uploadDir'] . $fileName;

		// Get file from S3 if provider is S3
		if ($config['storageProvider'] == self::STORAGE_PROVIDER_S3) {
			if (! file_exists($originalImagePath)) {
				static::downloadFromS3(ensurePathLastSlash($config['s3Config']->originalFilesPath) . $fileName, $originalImagePath, $config['s3Config']->configName);
			}
		}

		// Genereate image cache according to config
		$cacheImage = new Image($originalImagePath);
		Reg::get('imageMod')->modify($cacheImage, $config['cacheModelName'], $config['modelConfig']->modify);

		static::saveImage($cacheImage, $config['saveFormat'], $config['cacheDir'] . $config['cacheModelName'] . '/' . $fileName);

		unset($cacheImage);

		if ($config['storageProvider'] == self::STORAGE_PROVIDER_S3) {
			static::storeInS3($config['cacheDir'] . $config['cacheModelName'] . '/' . $fileName, ensurePathLastSlash($config['s3Config']->cachePath) . $config['cacheModelName'] . '/' . $fileName, $config['s3Config']->cacheFileACL, $config['s3Config']->configName);
		}
	}

	public static function getImageUrl($fileName, $typeName, $modelName) {
		try{
			$config = static::initConfigs($typeName, $modelName);
		}
		catch(InvalidArgumentException $e){
			return false;
		}

		if ($config['storageProvider'] == self::STORAGE_PROVIDER_FILESYSTEM) {
			$path = $config['cacheDir'] . $config['cacheModelName'] . '/' . $fileName;
			if (!file_exists($path)) {
				static::generateCache($fileName, $typeName, $modelName);
			}
			return SITE_PATH . $path;
		}
		elseif ($config['storageProvider'] == self::STORAGE_PROVIDER_S3) {
			$path = ensurePathLastSlash($config['s3Config']->cachePath) . $config['cacheModelName'] . '/' . $fileName;
			/* if(!S3Transport::fileExists($path, $s3Config->configName)){
			  throw new ImageManagerException('Image not found');
			  } */
			return S3Transport::getFileUrl($path, $config['s3Config']->configName);
		}

		return false;
	}
	
	public static function getImageContents($fileName, $typeName, $modelName, $outputFilename = null) {
		try{
			$config = static::initConfigs($typeName, $modelName);
		}
		catch(InvalidArgumentException $e){
			return false;
		}

		$return = [
			'length' => 0,
			'body' => ''
		];
		
		if ($config['storageProvider'] == self::STORAGE_PROVIDER_FILESYSTEM) {
			$path = $config['cacheDir'] . $config['cacheModelName'] . '/' . $fileName;
			if (!file_exists($path)) {
				static::generateCache($fileName, $typeName, $modelName);
			}
			
			$return['length'] = filesize($path);
			$return['body'] = file_get_contents($path);
			return $return;
		}
		elseif ($config['storageProvider'] == self::STORAGE_PROVIDER_S3) {
			$path = ensurePathLastSlash($config['s3Config']->cachePath) . $config['cacheModelName'] . '/' . $fileName;
			
			$result = S3Transport::get($path, $config['s3Config']->configName);
			$return['length'] = $result['ContentLength'];
			$return['body'] = $result['Body'];
			return $return;
		}

		return false;
	}
	
	public static function rotateImage($fileName, $typeName, $angle) {
		$config = static::initConfigs($typeName);
		
		$originalFilePath = $config['uploadDir'] . $fileName;
		
		static::deleteImageCache($fileName, $typeName);
		Reg::get('imageMod')->deleteCropSettings($fileName);
		
		// Get file from S3 if provider is S3
		if ($config['storageProvider'] == self::STORAGE_PROVIDER_S3) {
			static::downloadFromS3(ensurePathLastSlash($config['s3Config']->originalFilesPath) . $fileName, $originalFilePath, $config['s3Config']->configName);
		}
		
		$originalImage = new Image($originalFilePath);
		$originalImage->rotate($angle);
		static::saveImage($originalImage, $config['saveFormat'], $originalFilePath);
		
		if ($config['cacheEnabled'] === true and $config['preGenerateCacheOnUpload'] === true) {
			static::pregenerateCacheForAllModels($fileName, $typeName);
		}
		
		// Get file from S3 if provider is S3
		if ($config['storageProvider'] == self::STORAGE_PROVIDER_S3 and file_exists($originalFilePath)) {
			static::storeInS3($originalFilePath, ensurePathLastSlash($config['s3Config']->originalFilesPath) . $fileName, $config['s3Config']->originalFileACL, $config['s3Config']->configName);
		}
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
	public static function deleteImage($fileName, $typeName) {
		$config = static::initConfigs($typeName);
		
		if (file_exists($config['uploadDir'] . $fileName)) {
			unlink($config['uploadDir'] . $fileName);
		}

		Reg::get('imageMod')->deleteCropSettings($fileName);

		static::deleteImageCache($fileName, $typeName);
	}
	
	public static function deleteImageCache($fileName, $typeName) {
		$config = static::initConfigs($typeName);
		
		if ($config['cacheEnabled'] !== true) {
			return;
		}
		foreach (array_keys($config['config']->imageModels->toArray()) as $modelName) {
			$modelConfig = static::initConfigs($typeName, $modelName);

			if (file_exists($modelConfig['cacheDir'] . $modelConfig['cacheModelName'] . '/' . $fileName)) {
				unlink($modelConfig['cacheDir'] . $modelConfig['cacheModelName'] . '/' . $fileName);
			}

			if ($config['storageProvider'] == self::STORAGE_PROVIDER_S3) {
				static::deleteFromS3(ensurePathLastSlash($modelConfig['s3Config']->cachePath) . $modelConfig['cacheModelName'] . '/' . $fileName, $modelConfig['s3Config']->configName);
			}
		}
	}

	public static function getImageCropSettings($fileName, $typeName, $targetModelName, $preCropModelName) {
		$config = static::initConfigs($typeName, $targetModelName);
		$preCropConfig = static::initConfigs($typeName, $preCropModelName);
		
		
		$originalFilePath = $config['uploadDir'] . $fileName;
		$preCropFilePath = $preCropConfig['cacheDir'] . $preCropConfig['cacheModelName'] . '/' . $fileName;
		
		
		// Get file from S3 if provider is S3
		if ($config['storageProvider'] == self::STORAGE_PROVIDER_S3) {
			static::downloadFromS3(ensurePathLastSlash($config['s3Config']->originalFilesPath) . $fileName, $originalFilePath, $config['s3Config']->configName);
			static::downloadFromS3(ensurePathLastSlash($preCropConfig['s3Config']->cachePath) . $preCropConfig['cacheModelName'] . '/'. $fileName, $preCropFilePath, $preCropConfig['s3Config']->configName);
		}
		
		$originalImage = new Image($originalFilePath);
		$preCropImg = new Image($preCropFilePath);
		
		list($preWidth, $preHeight) = $preCropImg->getDimensions();

		$cropSettings = Reg::get('imageMod')->getCropSettings($fileName, $config['cacheModelName']);
		if ($cropSettings === false) {
			$cropSettings = ImageModificator::getDefaultCropSettings($originalImage, $config['modelConfig']->modify->crop->ratio);
		}
		$initSettings = ImageModificator::getProportionalCropCords($originalImage, $preCropImg, $cropSettings);

		$cropPropMinSize = Reg::get("imageMod")->getProportionalCropMinSize($originalImage, $preCropImg, $config['modelConfig']->modify);
		$previewWindow = Reg::get("imageMod")->getCropMinSize($config['modelConfig']->modify);

		$initCoords = array();
		$initCoords["x1"] = $initSettings->x;
		$initCoords["y1"] = $initSettings->y;
		$initCoords["x2"] = ceil($initSettings->x + $initSettings->width);
		$initCoords["y2"] = ceil($initSettings->y + $initSettings->height);

		$resultArray = array();
		$resultArray["imgSrc"] = static::getImageUrl($fileName, $typeName, $preCropModelName) . "?" . generateRandomString(10);
		$resultArray["imageWidth"] = $preWidth;
		$resultArray["imageHeight"] = $preHeight;
		$resultArray["ratioW"] = $previewWindow->width;
		$resultArray["ratioH"] = $previewWindow->height;
		$resultArray["initCoords"] = $initCoords;
		$resultArray["minCropWidth"] = $cropPropMinSize->width;
		$resultArray["minCropHeight"] = $cropPropMinSize->height;
		
		// Get file from S3 if provider is S3
		if ($config['storageProvider'] == self::STORAGE_PROVIDER_S3) {
			if(file_exists($originalFilePath)){
				unlink($originalFilePath);
			}
			if(file_exists($preCropFilePath)){
				unlink($preCropFilePath);
			}
		}

		return $resultArray;
	}

	public static function updateImageCropSettings($fileName, $coords, $typeName, $targetModelName, $preCropModelName) {
		$config = static::initConfigs($typeName, $targetModelName);
		$preCropConfig = static::initConfigs($typeName, $preCropModelName);
		
		$originalFilePath = $config['uploadDir'] . $fileName;
		$preCropFilePath = $preCropConfig['cacheDir'] . $preCropConfig['cacheModelName'] . '/' . $fileName;
		
		// Get file from S3 if provider is S3
		if ($config['storageProvider'] == self::STORAGE_PROVIDER_S3) {
			static::downloadFromS3(ensurePathLastSlash($config['s3Config']->originalFilesPath) . $fileName, $originalFilePath, $config['s3Config']->configName);
			static::downloadFromS3(ensurePathLastSlash($preCropConfig['s3Config']->cachePath) . $preCropConfig['cacheModelName'] . '/'. $fileName, $preCropFilePath, $preCropConfig['s3Config']->configName);
		}
		
		$originalImage = new Image($originalFilePath);
		$preCropImg = new Image($preCropFilePath);
		
		$cropParams = new Config(array('x' => $coords['x'], 'y' => $coords['y'], 'width' => $coords['width'], 'height' => $coords['height']));

		$cropSettings = ImageModificator::getProportionalCropCords($preCropImg, $originalImage, $cropParams);
		
		$targetRatio = $config['modelConfig']->modify->crop->ratio;
		
		foreach ($config['config']->imageModels->toArray() as $modelName => $modelConfig) {
			if(isset($modelConfig->modify) and isset($modelConfig->modify->crop) and isset($modelConfig->modify->crop->ratio) and $modelConfig->modify->crop->ratio == $targetRatio){
				Reg::get('imageMod')->saveCropSettings($originalImage, $targetRatio, $typeName . '-' . $modelName, $cropSettings);
				static::generateCache($fileName, $typeName, $modelName);
			}
		}

		// Get file from S3 if provider is S3
		if ($config['storageProvider'] == self::STORAGE_PROVIDER_S3) {
			if(file_exists($originalFilePath)){
				unlink($originalFilePath);
			}
			if(file_exists($preCropFilePath)){
				unlink($preCropFilePath);
			}
		}
	}

	protected static function saveImage(Image $image, $format, $savePath) {
		switch ($format) {
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
	}

	public static function checkIsEnoughMemory($filePath) {
		// Check if we have enough memory to open this file as image
		$info = getimagesize($filePath);

		if ($info != false && !Image::checkMemAvailbleForResize($info[0], $info[1])) {
			throw new ImageManagerException("Not enough memory to open image", static::EXCEPTION_IMAGE_IS_BIG);
		}
	}


	protected static function initConfigs($typeName, $modelName = null) {
		$resultingConfig = array();

		// Get configs
		$uploaderConfig = ConfigManager::getConfig('Image', 'ImageManager')->AuxConfig;

		if (!isset($uploaderConfig->imageTypes->$typeName)) {
			throw new InvalidArgumentException("There is no such image type $typeName");
		}
		$config = $uploaderConfig->imageTypes->$typeName;

		$resultingConfig['uploaderConfig'] = $uploaderConfig;
		$resultingConfig['config'] = $config;
		$resultingConfig['cacheEnabled'] = (isset($config->cacheEnabled) ? $config->cacheEnabled : $uploaderConfig->defaultCacheEnabled);
		$resultingConfig['storageProvider'] = (isset($config->storageProvider) ? $config->storageProvider : $uploaderConfig->defaultStorageProvider);
		$resultingConfig['acceptedMimeTypes'] = (isset($config->acceptedMimeTypes) ? $config->acceptedMimeTypes->toArray() : $uploaderConfig->defaultAcceptedMimeTypes->toArray());
		$resultingConfig['preGenerateCacheOnUpload'] = (isset($config->preGenerateCacheOnUpload) ? $config->preGenerateCacheOnUpload : $uploaderConfig->defaultPreGenerateCacheOnUpload);
		$resultingConfig['saveFormat'] = (isset($config->saveFormat) ? $config->saveFormat : $uploaderConfig->defaultSaveFormat);

		if ($resultingConfig['storageProvider'] == self::STORAGE_PROVIDER_S3) {
			$resultingConfig['s3Config'] = (isset($config->S3Config) ? $config->S3Config : $uploaderConfig->defaultS3Config);
		}

		$resultingConfig['uploadDir'] = (isset($config->uploadDir) ? $config->uploadDir : $uploaderConfig->defaultUploadDir);
		ensurePathLastSlash($resultingConfig['uploadDir']);
		if (empty($resultingConfig['uploadDir']) or ! file_exists($resultingConfig['uploadDir'])) {
			static::tryToMkdir($resultingConfig['uploadDir'], 0777);
		}


		if ($modelName !== null) {
			if (!isset($config->imageModels->$modelName)) {
				throw new InvalidArgumentException("There is no such model $modelName in type $typeName");
			}
			$resultingConfig['modelConfig'] = $config->imageModels->$modelName;
			$resultingConfig['cacheModelName'] = $typeName . '-' . $modelName;
			$resultingConfig['cropRatio'] = (isset($config->cropRatio) ? $config->cropRatio : null);

			$resultingConfig['cacheDir'] = (isset($config->cacheDir) ? $config->cacheDir : $uploaderConfig->defaultCacheDir);
			ensurePathLastSlash($resultingConfig['cacheDir']);
			if (empty($resultingConfig['cacheDir']) or ! file_exists($resultingConfig['cacheDir'] . $resultingConfig['cacheModelName'])) {
				static::tryToMkdir($resultingConfig['cacheDir'] . $resultingConfig['cacheModelName'], 0777);
			}
		}
		
		return $resultingConfig;
	}

	protected static function tryToMkdir($path, $mode) {
		if (!mkdir($path, 0777, $mode)) {
			throw new RuntimeException("There is no such folder {$path} and unable to create it!");
		}
	}

	/**
	 * Find new non conflicting filename
	 * 
	 * @param string $uploadDir
	 * @param string $imageFormat
	 */
	public static function findNewFileName($uploadDir, $imageFormat) {
		$fileName = static::generateUniqueFileName() . static::getAppropriateFileExtension($imageFormat);
		while (true) {
			if (file_exists($uploadDir . $fileName)) {
				$fileName = static::generateUniqueFileName() . static::getAppropriateFileExtension($imageFormat);
			}
			else {
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
	protected static function generateUniqueFileName() {
		return generateRandomString(64, [RANDOM_STRING_LOWERCASE, RANDOM_STRING_UPPERCASE, RANDOM_STRING_DIGITS]);
	}

	/**
	 * Get file extension by image type
	 * 
	 * @param string $imageFormat
	 * @return string
	 */
	protected static function getAppropriateFileExtension($imageFormat) {
		switch ($imageFormat) {
			case Image::IMAGE_TYPE_JPEG:
				return '.jpg';
			case Image::IMAGE_TYPE_PNG:
				return '.png';
			case Image::IMAGE_TYPE_GIF:
				return '.gif';
		}
	}

}
