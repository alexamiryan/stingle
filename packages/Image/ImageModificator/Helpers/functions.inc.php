<?php
function getUploaderConfigForModel($modelName){
	$uploaderConfig = ConfigManager::getConfig('Image', 'ImageUploader')->AuxConfig;
	$newUploaderConfig = clone($uploaderConfig);
	
	$ratio = ConfigManager::getConfig('Image', 'ImageModificator')->AuxConfig->imageModels->$modelName->actions->crop->ratio;
	list($ratioW, $ratioH) = explode(":", $ratio);
	
	$coefficient = 0;
	if($ratioW > $ratioH){
		$coefficient = $ratioW/$ratioH;
		$newUploaderConfig->minimumSizeStreight->minHeight = ConfigManager::getConfig('Image', 'ImageModificator')->AuxConfig->imageModels->$modelName->actions->crop->smallSideMinSize;
		$newUploaderConfig->minimumSizeStreight->minWidth = $newUploaderConfig->minimumSizeStreight->minHeight * $coefficient;
	}
	else{
		$coefficient = $ratioH/$ratioW;
		$newUploaderConfig->minimumSizeStreight->minWidth = ConfigManager::getConfig('Image', 'ImageModificator')->AuxConfig->imageModels->$modelName->actions->crop->smallSideMinSize;
		$newUploaderConfig->minimumSizeStreight->minHeight = $newUploaderConfig->minimumSizeStreight->minWidth * $coefficient;
	}
	
	return $newUploaderConfig;
}
