<?php

function smarty_modifier_imagePath($fileName, $type, $model) {
	return ImageManager::getImageUrl($fileName, $type, $model);
}
