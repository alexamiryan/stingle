<?php

function smarty_modifier_imagePath($fileName, $type, $model, $hostNameForFilesystem = null) {
	return ImageManager::getImageUrl($fileName, $type, $model, $hostNameForFilesystem);
}
